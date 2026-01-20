import mysql.connector
from mysql.connector import Error
from datetime import datetime, timedelta
import random
import string
import sys

# --- CONFIGURATION ---
simulation = 360
start_date = datetime.now() - timedelta(days=simulation)
bag_counter = 1000000
used_pnrs = set()

stats = {
    "total_flights": 0, "total_bookings": 0, "total_baggage": 0,
    "total_delays": 0, "cancelled": 0, "errors": 0, "resyncs": 0,
    "count_89": 0
}

# --- TABLE IATA COMPLÈTE ---
IATA_CODES = {
    "INTERNE": [0, 1, 2, 3, 4, 5],
    "PASSAGER": [11, 12, 13, 14, 15, 16, 17, 18, 19],
    "FRET": [21, 22, 23, 24, 25, 26],
    "OPERATIONS": [8, 31, 32, 33, 34, 35, 52, 53],
    "TECHNIQUE": [41, 42, 43, 44, 45, 46, 47, 48, 51],
    "PERSONNEL": [6, 61, 62, 63, 64, 65, 66],
    "METEO": [71, 72, 73, 75, 76, 77],
    "ATC": [81, 82, 83, 84, 85, 86, 87, 88],
    "REPERCUSSION": [89, 91, 92, 93, 94, 95, 96]
}

def create_db_connection():
    try:
        return mysql.connector.connect(
            host='localhost', user='admin', password='admin', database='db_airline_V4',
            charset='utf8mb4', collation='utf8mb4_unicode_ci'
        )
    except Error as e:
        print(f"Erreur connexion : {e}")
        sys.exit(1)

def get_real_aircraft_status(cursor, aircraft_id, current_day_date):
    """Interroge la base pour connaître la position et l'heure réelle de fin du dernier vol ARRIVED."""
    query = """
        SELECT r.arrival_airport, f.actual_arrival
        FROM flights f
        JOIN routes r ON f.route_id = r.route_id
        WHERE f.aircraft_id = %s AND f.status = 'ARRIVED'
        ORDER BY f.actual_arrival DESC, f.flight_id DESC
        LIMIT 1
    """
    cursor.execute(query, (aircraft_id,))
    res = cursor.fetchone()
    if res:
        return res['arrival_airport'], res['actual_arrival']
    return 'CDG', current_day_date.replace(hour=6, minute=0, second=0)

def pick_realistic_delay(carry_over):
    """Calibrage pour équilibrer le graphique (réduction de l'effet domino)."""
    if carry_over > 30:
        if random.random() < 0.70:
            # On transmet le code 89 mais on absorbe 20% du retard
            return 89, int(carry_over * 0.8)

    roll = random.random()
    if roll < 0.02: return random.choice(IATA_CODES["METEO"]), random.randint(45, 180)
    if roll < 0.05: return random.choice(IATA_CODES["TECHNIQUE"]), random.randint(20, 90)
    if roll < 0.09: return random.choice(IATA_CODES["PASSAGER"]), random.randint(10, 25)
    if roll < 0.13: return random.choice(IATA_CODES["OPERATIONS"]), random.randint(10, 35)
    if roll < 0.16: return random.choice(IATA_CODES["ATC"]), random.randint(5, 20)

    return None, 0

def insert_flight(cursor, aircraft, route, departure_time, staff, all_pax, carry_over_delay):
    global bag_counter, stats

    iata_code, new_delay = pick_realistic_delay(carry_over_delay)
    total_delay = (carry_over_delay if iata_code == 89 else 0) + new_delay

    status = 'ARRIVED'
    if total_delay > 420: status = 'CANCELLED'

    duration = route['avg_flight_duration_minutes']
    sched_arr = departure_time + timedelta(minutes=duration)
    act_dep = departure_time + timedelta(minutes=total_delay)
    act_arr = sched_arr + timedelta(minutes=total_delay)

    try:
        cursor.execute("""
            INSERT INTO flights (flight_number, route_id, aircraft_id, scheduled_departure,
            scheduled_arrival, actual_departure, actual_arrival, status)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """, (f"AF{random.randint(100, 9999)}", route['route_id'], aircraft['aircraft_id'],
              departure_time, sched_arr, act_dep, act_arr, status))

        f_id = cursor.lastrowid
        stats["total_flights"] += 1

        if status == 'CANCELLED':
            stats["cancelled"] += 1
            return None, 0 # On retourne None pour forcer la synchro après annulation

        if total_delay > 0 and iata_code:
            stats["total_delays"] += 1
            if iata_code == 89: stats["count_89"] += 1
            cursor.execute("INSERT INTO flight_delays (flight_id, code_id, delay_minutes) VALUES (%s, %s, %s)",
                         (f_id, iata_code, total_delay))

        # Équipage
        crew_ids = [e['employee_id'] for e in staff]
        if len(crew_ids) >= 2:
            selected = random.sample(crew_ids, 2)
            for emp_id in selected:
                cursor.execute("INSERT INTO flight_crew (flight_id, employee_id) VALUES (%s, %s)", (f_id, emp_id))

        # Passagers & Bagages
        cursor.execute("SELECT seat_id, seat_number, class FROM seats WHERE model_id = %s", (aircraft['model_id'],))
        seats = cursor.fetchall()
        num_pax = int(len(seats) * random.uniform(0.75, 0.95))
        sampled_pax = random.sample(all_pax, min(num_pax, len(all_pax)))

        for i in range(len(sampled_pax)):
            pnr = ''.join(random.choices(string.ascii_uppercase + string.digits, k=6))
            cursor.execute("""
                INSERT INTO bookings (flight_id, passenger_id, pnr, seat_id, seat_number, class, price_paid)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """, (f_id, sampled_pax[i], pnr, seats[i]['seat_id'], seats[i]['seat_number'], seats[i]['class'], random.randint(60, 450)))

            b_id = cursor.lastrowid
            if random.random() > 0.35:
                bag_counter += 1
                cursor.execute("INSERT INTO baggage (booking_id, tag_number, weight_kg) VALUES (%s, %s, %s)",
                             (b_id, f"TAG{bag_counter}", round(random.uniform(12, 24), 1)))
                stats["total_baggage"] += 1

        stats["total_bookings"] += len(sampled_pax)
        return act_arr, total_delay

    except Error as err:
        stats["errors"] += 1
        print(f"   [!] Erreur SQL Avion {aircraft['aircraft_id']} : {err.msg}")
        return None, 0

def run_simulation():
    db = create_db_connection()
    cursor = db.cursor(dictionary=True)

    print("Nettoyage des données existantes...")
    cursor.execute("SET FOREIGN_KEY_CHECKS = 0;")
    for t in ['baggage', 'bookings', 'flight_crew', 'flight_delays', 'flights']:
        cursor.execute(f"TRUNCATE TABLE {t};")
    cursor.execute("SET FOREIGN_KEY_CHECKS = 1;")

    cursor.execute("SELECT aircraft_id, model_id FROM aircrafts")
    fleet = cursor.fetchall()
    cursor.execute("SELECT * FROM routes")
    routes = cursor.fetchall()
    cursor.execute("SELECT employee_id, role FROM employees")
    staff = cursor.fetchall()
    cursor.execute("SELECT passenger_id FROM passengers")
    all_pax = [p['passenger_id'] for p in cursor.fetchall()]

    for day in range(simulation):
        curr_date = start_date + timedelta(days=day)

        for aircraft in fleet:
            a_id = aircraft['aircraft_id']
            # Reset matinal
            loc, last_time = get_real_aircraft_status(cursor, a_id, curr_date)
            curr_time = max(curr_date.replace(hour=6, minute=0), last_time + timedelta(minutes=45))
            retard_cumule = 0

            while curr_time.hour < 23:
                opts = [r for r in routes if r['departure_airport'] == loc]
                if not opts:
                    # Fallback si l'avion est "perdu" dans un aéroport sans routes
                    loc = 'CDG'
                    continue

                route = random.choice(opts)
                arrival, delay = insert_flight(cursor, aircraft, route, curr_time, staff, all_pax, retard_cumule)

                if arrival:
                    # SUCCÈS
                    loc = route['arrival_airport']
                    retard_cumule = max(0, delay - 35) # Buffer d'escale de 35 min
                    curr_time = arrival + timedelta(minutes=random.randint(45, 75))
                else:
                    # ÉCHEC OU ANNULATION -> SORTIE DE BOUCLE INFINIE
                    db.rollback()
                    stats["resyncs"] += 1

                    # On interroge la base pour savoir où on en est vraiment
                    loc_db, time_db = get_real_aircraft_status(cursor, a_id, curr_date)

                    # Sécurité : on avance le temps de force pour casser la boucle
                    new_time = max(curr_time, time_db) + timedelta(hours=1, minutes=30)

                    print(f"   [RE-SYNC] Avion {a_id} : repositionné à {loc_db} (Time Jump to {new_time.strftime('%H:%M')})")

                    loc = loc_db
                    curr_time = new_time
                    retard_cumule = 0

                    if curr_time.day > curr_date.day or curr_time.hour >= 23:
                        break

        db.commit()
        if (day + 1) % 10 == 0:
            print(f"Progression : {day+1}/{simulation} jours traités.")

    # --- BILAN FINAL ---
    print("\n" + "="*50)
    print("         BILAN OPÉRATIONNEL COMPLET")
    print("="*50)
    otp = ((stats['total_flights'] - stats['total_delays']) / max(1, stats['total_flights'])) * 100
    print(f" Vols Totaux      : {stats['total_flights']}")
    print(f" Ponctualité (OTP): {round(otp, 1)}%")
    print(f" Annulations      : {stats['cancelled']}")
    print(f" Bagages créés    : {stats['total_baggage']}")
    print(f" Synchronisations : {stats['resyncs']}")
    print(f" Erreurs SQL      : {stats['errors']}")
    print("="*50)
    db.close()

if __name__ == "__main__":
    run_simulation()
