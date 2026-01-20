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

# Générateur de PNR (6 caractères Alphanumériques uniques)
def generate_pnr():
    chars = string.ascii_uppercase + string.digits
    return ''.join(random.choice(chars) for _ in range(6))

bag_counter = 1000000

def insert_flight(cursor, aircraft, route, departure_time, staff, all_passengers):
    global bag_counter
    
    # Simulation des retards (12% de chance)
    delay = random.randint(15, 90) if random.random() < 0.12 else 0
    duration = route['avg_flight_duration_minutes']
    
    sched_arr = departure_time + timedelta(minutes=duration)
    act_dep = departure_time + timedelta(minutes=delay)
    act_arr = sched_arr + timedelta(minutes=delay)
    
    try:
        # 1. Insertion du Vol
        cursor.execute("""
            INSERT INTO flights (flight_number, route_id, aircraft_id, scheduled_departure, scheduled_arrival, actual_departure, actual_arrival, status)
            VALUES (%s, %s, %s, %s, %s, %s, %s, 'ARRIVED')
        """, (f"AF{random.randint(100, 9999)}", route['route_id'], aircraft['aircraft_id'], departure_time, sched_arr, act_dep, act_arr))
        f_id = cursor.lastrowid

        # 2. Retards
        if delay > 0:
            cursor.execute("INSERT INTO flight_delays (flight_id, code_id, delay_minutes) VALUES (%s, %s, %s)",
                         (f_id, random.choice([11, 41, 81]), delay))

        # 3. Equipage
        caps = [e['employee_id'] for e in staff if e['role'] == 'CAPTAIN']
        fos = [e['employee_id'] for e in staff if e['role'] == 'FIRST_OFFICER']
        for p_id in [random.choice(caps), random.choice(fos)]:
            cursor.execute("INSERT INTO flight_crew (flight_id, employee_id) VALUES (%s, %s)", (f_id, p_id))

        # 4. Bookings avec PNR & Baggage
        cursor.execute("SELECT seat_id, seat_number, class FROM seats WHERE model_id = %s", (aircraft['model_id'],))
        seats = cursor.fetchall()
        
        num_pax = int(len(seats) * random.uniform(0.7, 0.98))
        sampled_pax = random.sample(all_passengers, num_pax)
        
        for i in range(num_pax):
            s = seats[i]
            price = random.randint(200, 600) if s['class'] == 'BUS' else random.randint(40, 180)
            current_pnr = generate_pnr() # Création du PNR unique
            
            cursor.execute("""
                INSERT INTO bookings (flight_id, passenger_id, pnr, seat_id, seat_number, class, price_paid)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """, (f_id, sampled_pax[i], current_pnr, s['seat_id'], s['seat_number'], s['class'], price))
            
            b_id = cursor.lastrowid
            if random.random() > 0.4:
                bag_counter += 1
                cursor.execute("INSERT INTO baggage (booking_id, tag_number, weight_kg) VALUES (%s, %s, %s)",
                             (b_id, f"TAG{bag_counter}", round(random.uniform(12, 24), 1)))
        
        # On retourne l'heure d'arrivée RÉELLE pour synchroniser le vol suivant
        return act_arr

    except mysql.connector.Error as err:
        print(f"Blocage Trigger sur {aircraft['aircraft_id']} : {err.msg}")
        return None

def run_simulation():
    db = create_db_connection()
    cursor = db.cursor(dictionary=True)
    
    # --- NETTOYAGE ---
    print("Nettoyage des tables...")
    cursor.execute("SET FOREIGN_KEY_CHECKS = 0;")
    for table in ['baggage', 'bookings', 'flight_crew', 'flight_delays', 'flights']:
        cursor.execute(f"TRUNCATE TABLE {table};")
    cursor.execute("SET FOREIGN_KEY_CHECKS = 1;")

    # --- CHARGEMENT RÉFÉRENTIEL ---
    cursor.execute("SELECT aircraft_id, model_id FROM aircrafts")
    fleet = cursor.fetchall()
    cursor.execute("SELECT * FROM routes")
    routes = cursor.fetchall()
    cursor.execute("SELECT employee_id, role FROM employees")
    staff = cursor.fetchall()
    cursor.execute("SELECT passenger_id FROM passengers")
    all_passengers = [p['passenger_id'] for p in cursor.fetchall()]

    # Initialisation des positions
    aircraft_states = {a['aircraft_id']: {'loc': 'CDG'} for a in fleet}
    start_date = datetime(2026, 2, 1, 6, 0)

    print("Début de la simulation...")

    for day in range(90):
        for aircraft in fleet:
            a_id = aircraft['aircraft_id']
            current_loc = aircraft_states[a_id]['loc']
            
            # Heure de départ : 6h + aléatoire
            current_time = (start_date + timedelta(days=day)).replace(hour=6, minute=0) + timedelta(minutes=random.randint(0, 60))

            for _ in range(2): # 2 Allers-Retours
                # ALLER
                opts_out = [r for r in routes if r['departure_airport'] == current_loc]
                if not opts_out: break
                route_out = random.choice(opts_out)
                
                # On récupère l'heure de fin réelle du vol
                arrival_time = insert_flight(cursor, aircraft, route_out, current_time, staff, all_passengers)
                
                if arrival_time:
                    current_loc = route_out['arrival_airport']
                    # ESCALE : Prochain départ = Arrivée Réelle + 45-90 min
                    current_time = arrival_time + timedelta(minutes=random.randint(45, 90))

                    # RETOUR
                    opts_in = [r for r in routes if r['departure_airport'] == current_loc]
                    if opts_in:
                        route_in = random.choice(opts_in)
                        arrival_time_ret = insert_flight(cursor, aircraft, route_in, current_time, staff, all_passengers)
                        if arrival_time_ret:
                            current_loc = route_in['arrival_airport']
                            current_time = arrival_time_ret + timedelta(minutes=random.randint(45, 90))
                else:
                    # Si le vol est bloqué (Trigger), on stoppe la journée de cet avion
                    break

            aircraft_states[a_id]['loc'] = current_loc

        db.commit()
        if (day + 1) % 10 == 0:
            print(f"Progression : {day + 1} jours simulés...")

    cursor.close()
    db.close()
    print("\nSimulation terminée avec succès !")

if __name__ == "__main__":
    run_simulation()
