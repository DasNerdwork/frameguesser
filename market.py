import requests
import time
import csv
import os

BASE_URL = "https://api.warframe.market/v1"
HEADERS = {
    "accept": "application/json",
    "Platform": "pc",
    "Language": "en"
}

CSV_FILE = "cheapest_orders.csv"

def fetch_all_items():
    url = f"{BASE_URL}/items"
    response = requests.get(url, headers=HEADERS)
    response.raise_for_status()
    return response.json()["payload"]["items"]

def fetch_orders_for_item(url_name):
    url = f"{BASE_URL}/items/{url_name}/orders"
    response = requests.get(url, headers=HEADERS)
    response.raise_for_status()
    return response.json()["payload"]["orders"]

def main():
    items = fetch_all_items()
    print(f"{len(items)} Items gefunden")

    # CSV vorbereiten
    file_exists = os.path.isfile(CSV_FILE)
    with open(CSV_FILE, mode='a', newline='', encoding='utf-8') as csvfile:
        fieldnames = ["item_name", "platinum", "quantity", "seller"]
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)

        if not file_exists:
            writer.writeheader()

        try:
            for i, item in enumerate(items):
                url_name = item["url_name"]
                try:
                    orders = fetch_orders_for_item(url_name)
                    active_sell_orders = [
                        o for o in orders if o["order_type"] == "sell" and o["user"]["status"] == "ingame"
                    ]
                    if not active_sell_orders:
                        continue

                    cheapest_order = min(active_sell_orders, key=lambda x: x["platinum"])
                    row = {
                        "item_name": item["item_name"],
                        "platinum": cheapest_order["platinum"],
                        "quantity": cheapest_order["quantity"],
                        "seller": cheapest_order["user"]["ingame_name"]
                    }
                    writer.writerow(row)
                    csvfile.flush()  # Sicherstellen, dass es auf Festplatte geschrieben wird
                    print(f"[{i+1}] Gespeichert: {row['item_name']} für {row['platinum']}p von {row['seller']}")

                except Exception as e:
                    print(f"Fehler bei {url_name}: {e}")

                time.sleep(0.3)  # API-Schonung

        except KeyboardInterrupt:
            print("\nAbbruch durch Benutzer – Datei wurde gespeichert.")
        except Exception as e:
            print(f"\nUnerwarteter Fehler: {e}")

if __name__ == "__main__":
    main()
