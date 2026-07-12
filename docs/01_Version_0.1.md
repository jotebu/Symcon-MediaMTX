# Version 0.1 – MediaMTX Control API testen

## Ziel

Version 0.1 stellt die grundlegende Verbindung zwischen IP-Symcon und der MediaMTX Control API her.

Verwendeter Endpunkt:

```text
GET /v3/info
```

## Funktionsumfang

- Konfiguration von Host oder IP-Adresse
- Konfiguration des Control-API-Ports
- HTTP oder HTTPS
- optionale TLS-Zertifikatsprüfung
- einstellbares Aktualisierungsintervall
- manueller Verbindungstest
- automatische zyklische Abfrage
- Anzeige der MediaMTX-Version
- Anzeige des letzten erfolgreichen Abrufs
- Anzeige des letzten Fehlers
- Speicherung der vollständigen JSON-Antwort zur Diagnose
- Debug-Ausgabe in IP-Symcon

## Erzeugte Variablen

| Ident | Typ | Bedeutung |
|---|---|---|
| `APIReachable` | Boolean | Control API erreichbar und Antwort gültig |
| `Version` | String | MediaMTX-Version aus `/v3/info` |
| `LastSuccessfulUpdate` | String | Zeitpunkt des letzten erfolgreichen Abrufs |
| `LastError` | String | letzter Verbindungs- oder Auswertungsfehler |
| `RawInfo` | String | formatierte vollständige JSON-Antwort |

## Standardwerte

| Einstellung | Standardwert |
|---|---:|
| Host | `127.0.0.1` |
| Control-API-Port | `9997` |
| HTTPS | Nein |
| TLS prüfen | Ja |
| Aktualisierungsintervall | 30 Sekunden |

## MediaMTX-Konfiguration

Die Control API muss in MediaMTX aktiviert sein. Beispiel:

```yaml
api: yes
apiAddress: :9997
```

Bei Zugriff von einem anderen System muss die Control API auf einer geeigneten Netzwerkschnittstelle lauschen. Der Zugriff sollte ausschließlich in einem vertrauenswürdigen Netz oder über eine abgesicherte Verbindung erfolgen.

## Noch nicht Bestandteil von Version 0.1

- Kamerapfade
- aktive Streams
- Clients oder Reader
- MediaMTX-Metriken
- Bitraten
- KNX-Ausgabe
- Authentifizierung

Diese Funktionen werden nach erfolgreichem Praxistest der Basisverbindung schrittweise ergänzt.
