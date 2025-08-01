#!/usr/bin/env python3
# tools/plot_sine.py

import matplotlib
matplotlib.use('Agg')
import io, os, base64, tempfile
import numpy as np
import matplotlib.pyplot as plt
from matplotlib.animation import FuncAnimation, PillowWriter

# persistent phase storage
STATE = os.path.expanduser('~/.sine_phase_state')
try:
    with open(STATE) as f:
        phase = float(f.read())
except OSError:
    phase = 0.0

# advance phase in [0, 2π)
phase = (phase + 0.2) % (2 * np.pi)
with open(STATE, 'w') as f:
    f.write(str(phase))

FRAMES = 120  # finer resolution
FPS = 20      # smoother animation

# prepare figure with extra bottom margin for x-labels
dpi = 100
fig, ax = plt.subplots(figsize=(6, 3), dpi=dpi)
fig.subplots_adjust(bottom=0.2)

ax.set_title("Animated Sine Wave", fontsize=14)
ax.set_xlabel("θ (radians)", fontsize=12)
ax.set_ylabel("sin θ", fontsize=12)
ax.grid(True, linestyle='--', alpha=0.6)

# fixed window [0, 2π]
ax.set_xlim(0, 2 * np.pi)
ax.set_ylim(-1.2, 1.2)
ticks = np.pi * np.array([0, .5, 1, 1.5, 2])
ax.set_xticks(ticks)
ax.set_xticklabels(["0", "π/2", "π", "3π/2", "2π"], fontsize=10)

line, = ax.plot([], [], lw=2)

# make sure layout is tight so labels aren’t cut off
plt.tight_layout()

def update(frame):
    ph = phase + 2 * np.pi * (frame / FRAMES)
    x = np.linspace(ph, ph + 2 * np.pi, 400)
    y = np.sin(x)
    line.set_data(x - ph, y)
    return (line,)

ani = FuncAnimation(fig, update, frames=FRAMES, blit=True)

# save to temp GIF
with tempfile.NamedTemporaryFile(suffix='.gif', delete=False) as tmp:
    gif_path = tmp.name

writer = PillowWriter(fps=FPS)
ani.save(gif_path, writer=writer)
plt.close(fig)

with open(gif_path, 'rb') as f:
    data = f.read()
os.remove(gif_path)

print(base64.b64encode(data).decode(), end="")
