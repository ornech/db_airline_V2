import mysql.connector
from mysql.connector import Error
from datetime import datetime, timedelta
import random
import string

def create_db_connection():
    connection = mysql.connector.connect(
        host='localhost', user='admin', password='admin', database='db_airline_V4',
        charset='utf8mb4', collation='utf8mb4_unicode_ci'
    )
    if connection.is_connected():
        cursor = connection.cursor()
        cursor.execute("SET time_zone = '+00:00';")
        cursor.close()
    return connection

def generate_pnr():
    chars = string.ascii_uppercase + string.digits
    return ''.join(random.choice(chars) for _ in range(6))

# --- CONFIGURATION DES ÉVÉNEMENTS ---
CRISES = [
    {
        "nom": "Tempête Ciaran",
        "debut": datetime(2026, 2, 10),
        "fin": datetime(2026, 2, 12),
        "cible": "LHR",           
        "probabilite": 0.85,
        "retard_min": 150,
        "code_iata": 72           
    },
    {
        "nom": "Grève Contrôleurs Aériens",
        "debut": datetime(2026, 3, 5),
        "fin": datetime(2026, 3, 10),
        "cible": "CDG",           
        "probabilite": 0.60,
        "retard_min": 90,
        "code_iata": 81           
    }
]

def get_crisis_impact(current_time, dep_apt, arr_apt):
    for c in CRISES:
        if c['debut'] <= current_time <= c['fin']:
            if c['cible'] in [dep_apt, arr_apt, "GLOBAL"]:
                if random.random() < c['probabilite']:
                    return c
    return None

bag_counter = 1000000

def insert_flight(cursor, aircraft, route, departure_time, staff, all_passengers, carry_over_delay=0):
    global bag_counter
    
    # 1. Calcul du retard et du statut
    crise = get_crisis_impact(departure_time, route['departure_airport'], route['arrival_airport'])
    new_delay = 0
    iata_code = None

    if crise:
        new_delay = random.randint(crise['retard_min'], crise['retard_min'] + 180)
        iata_code = crise['code_iata']
    else:
        chance = 0.40 if carry_over_delay > 0 else 0.12
        if random.random() < chance:
            new_delay = random.randint(15, 60)
            iata_code = 89 if carry_over_delay > 0 else random.choice([11, 41, 15, 18, 43])

    total_delay = carry_over_delay + new_delay
    status = 'ARRIVED'
    
    # Si retard > 6h (360 min) -> ANNULATION
    if total_delay > 360:
        status = 'CANCELLED'
        total_delay = 0 

    duration = route['avg_flight_duration_minutes']
    sched_arr = departure_time + timedelta(minutes=duration)
    act_dep = departure_time + timedelta(minutes=total_delay)
    act_arr = sched_arr + timedelta(minutes=total_delay)

    try:
        # A. Insertion du Vol
        cursor.execute("""
            INSERT INTO flights (flight_number, route_id, aircraft_id, scheduled_departure, scheduled_arrival, actual_departure, actual_arrival, status)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """, (f"AF{random.randint(100, 9999)}", route['route_id'], aircraft['aircraft_id'], departure_time, sched_arr, act_dep, act_arr, status))
        f_id = cursor.lastrowid

        if status == 'CANCELLED':
            return departure_time + timedelta(hours=4), 0

        # B. Retards (Code 89 par défaut si répercussion)
        if total_delay > 0:
            cursor.execute("INSERT INTO flight_delays (flight_id, code_id, delay_minutes) VALUES (%s, %s, %s)",
                         (f_id, iata_code or 89, total_delay))

        # C. Équipage
        caps = [e['employee_id'] for e in staff if e['role'] == 'CAPTAIN']
        fos = [e['employee_id'] for e in staff if e['role'] == 'FIRST_OFFICER']
        for p_id in [random.choice(caps), random.choice(fos)]:
            cursor.execute("INSERT INTO flight_crew (flight_id, employee_id) VALUES (%s, %s)", (f_id, p_id))

        # D. Passagers et Billets (Seulement si vol maintenu)
        cursor.execute("SELECT seat_id, seat_number, class FROM seats WHERE model_id = %s", (aircraft['model_id'],))
        seats = cursor.fetchall()
        
        num_pax = int(len(seats) * random.uniform(0.65, 0.98))
        sampled_pax = random.sample(all_passengers, num_pax)
        
        for i in range(num_pax):
            s = seats[i]
            price = random.randint(200, 600) if s['class'] == 'BUS' else random.randint(40, 180)
            pnr = generate_pnr()
            
            cursor.execute("""
                INSERT INTO bookings (flight_id, passenger_id, pnr, seat_id, seat_number, class, price_paid)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """, (f_id, sampled_pax[i], pnr, s['seat_id'], s['seat_number'], s['class'], price))
            
            b_id = cursor.lastrowid
            # Bagages (60% de chance)
            if random.random() > 0.4:
                bag_counter += 1
                cursor.execute("INSERT INTO baggage (booking_id, tag_number, weight_kg) VALUES (%s, %s, %s)",
                             (b_id, f"TAG{bag_counter}", round(random.uniform(12, 24), 1)))
        
        return act_arr, total_delay
    except mysql.connector.Error as err:
        print(f"Erreur SQL : {err.msg}")
        return None, 0

def run_simulation():
    db = create_db_connection()
    cursor = db.cursor(dictionary=True)
    
    print("Vidage des tables existantes...")
    cursor.execute("SET FOREIGN_KEY_CHECKS = 0;")
    for t in ['baggage', 'bookings', 'flight_crew', 'flight_delays', 'flights']:
        cursor.execute(f"TRUNCATE TABLE {t};")
    cursor.execute("SET FOREIGN_KEY_CHECKS = 1;")

    # Chargement du référentiel
    cursor.execute("SELECT aircraft_id, model_id FROM aircrafts")
    fleet = cursor.fetchall()
    cursor.execute("SELECT * FROM routes")
    routes = cursor.fetchall()
    cursor.execute("SELECT employee_id, role FROM employees")
    staff = cursor.fetchall()
    cursor.execute("SELECT passenger_id FROM passengers")
    all_passengers = [p['passenger_id'] for p in cursor.fetchall()]

    aircraft_states = {a['aircraft_id']: {'loc': 'CDG'} for a in fleet}
    start_date = datetime(2026, 2, 1, 6, 0)

    print("Début de la simulation (90 jours)...")

    for day in range(90):
        current_day_date = start_date + timedelta(days=day)
        for aircraft in fleet:
            a_id = aircraft['aircraft_id']
            current_loc = aircraft_states[a_id]['loc']
            current_time = current_day_date.replace(hour=6, minute=0) + timedelta(minutes=random.randint(0, 45))
            cumul_retard = 0

            # 2 Rotations par avion
            for _ in range(2):
                opts = [r for r in routes if r['departure_airport'] == current_loc]
                if not opts: break
                route = random.choice(opts)
                
                arrival, cumul_retard = insert_flight(cursor, aircraft, route, current_time, staff, all_passengers, cumul_retard)
                
                if arrival:
                    current_loc = route['arrival_airport']
                    # Escale : on réduit le retard de 15 min grâce à la réactivité au sol
                    cumul_retard = max(0, cumul_retard - 15)
                    current_time = arrival + timedelta(minutes=random.randint(45, 75))

            aircraft_states[a_id]['loc'] = current_loc
        
        db.commit()
        if (day + 1) % 10 == 0:
            print(f"Progression : {day + 1}/90 jours simulés.")

    db.close()
    print("\nSimulation terminée avec succès !")

if __name__ == "__main__":
    run_simulation()
