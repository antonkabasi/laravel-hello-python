#!/usr/bin/env python3
# tools/stream_writer.py

import sqlite3, time, random, os
from datetime import datetime

PROJECT_ROOT = os.path.abspath(os.path.join(__file__, '..', '..'))
DB = os.path.join(PROJECT_ROOT, 'database', 'database.sqlite')
conn = sqlite3.connect(DB, isolation_level=None)
c = conn.cursor()

while True:
    ts  = datetime.utcnow().isoformat()
    val = random.random()
    now = datetime.utcnow().isoformat()
    c.execute(
      "INSERT INTO stream_data (timestamp, value, created_at, updated_at) VALUES (?,?,?,?)",
      (ts, val, now, now)
    )
    time.sleep(1)
