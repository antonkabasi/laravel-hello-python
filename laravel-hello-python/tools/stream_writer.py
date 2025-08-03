# tools/stream_writer.py
#!/usr/bin/env python3
import sqlite3, time, random, os
from datetime import datetime

PROJECT_ROOT = os.path.abspath(os.path.join(__file__, '..', '..'))
DB = os.path.join(PROJECT_ROOT, 'database', 'database.sqlite')

conn = sqlite3.connect(DB, isolation_level=None)  # autocommit
conn.execute("PRAGMA journal_mode=WAL;")
conn.execute("PRAGMA busy_timeout=3000;")
c = conn.cursor()

# Optional: if you *want* timestamp uniqueness, create a unique index once:
# c.execute("CREATE UNIQUE INDEX IF NOT EXISTS ux_stream_timestamp ON stream_data(timestamp);")

while True:
    ts  = datetime.utcnow().isoformat(timespec='microseconds')  # microsecond precision
    val = random.random()
    now = datetime.utcnow().isoformat(timespec='microseconds')
    try:
        # If a unique index exists, skip duplicates gracefully:
        c.execute(
          "INSERT OR IGNORE INTO stream_data (timestamp, value, created_at, updated_at) VALUES (?,?,?,?)",
          (ts, val, now, now)
        )
    except sqlite3.IntegrityError:
        # If you prefer overwriting instead of ignoring, switch to INSERT OR REPLACE
        pass
    time.sleep(1)
