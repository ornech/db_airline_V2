import mysql.connector
from mysql.connector import Error

def create_db_connection():
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='admin',
            password='admin',
            database='db_airline_V4',
            charset='utf8mb4',
            collation='utf8mb4_unicode_ci'
        )
        if connection.is_connected():
            print("Connexion à la base de données réussie !")
            return connection
    except Error as e:
        print(f"Erreur lors de la connexion : {e}")
        return None

def generate_seats(cursor):
    # Configuration des modèles basées sur ton dump
    # 1: A320, 2: A350, 3: B737
    configs = {
        1: {'rows': 27, 'layout': 'ABCDEF', 'bus_until': 3},
        2: {'rows': 35, 'layout': 'ABCDEFGHJ', 'bus_until': 5},
        3: {'rows': 30, 'layout': 'ABCDEF', 'bus_until': 3}
    }

    print("Génération des sièges en cours...")
    
    for model_id, cfg in configs.items():
        seats_to_insert = []
        for r in range(1, cfg['rows'] + 1):
            for letter in cfg['layout']:
                seat_num = f"{r}{letter}"
                s_class = 'BUS' if r <= cfg['bus_until'] else 'ECO'
                is_exit = 1 if r in [12, 13] else 0
                
                seats_to_insert.append((model_id, seat_num, s_class, is_exit))
        
        query = "INSERT INTO seats (model_id, seat_number, class, is_exit_row) VALUES (%s, %s, %s, %s)"
        cursor.executemany(query, seats_to_insert)
        print(f"Modèle {model_id} : {len(seats_to_insert)} sièges préparés.")

# --- EXECUTION DU SCRIPT ---

db = create_db_connection()

if db:
    cursor = db.cursor()
    try:
        # 1. On appelle la fonction !
        generate_seats(cursor)
        
        # 2. IMPORTANT : On valide les changements
        db.commit()
        print("Toutes les données ont été enregistrées (COMMIT) avec succès.")
        
    except Error as e:
        print(f"Erreur pendant l'insertion : {e}")
        db.rollback() # Annule en cas d'erreur
    finally:
        # 3. On ferme proprement
        cursor.close()
        db.close()
        print("Connexion fermée.")
