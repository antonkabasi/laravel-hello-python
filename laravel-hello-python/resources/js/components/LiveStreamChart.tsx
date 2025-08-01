// resources/js/Components/LiveStreamChart.tsx
import { useEffect, useRef, useState } from 'react';
import Chart from 'chart.js/auto';

interface Point { timestamp: string; value: number; }

export default function LiveStreamChart() {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const chartRef  = useRef<Chart|null>(null);
  const [data, setData] = useState<Point[]>([]);

  // 1️⃣ Initialize the chart
  useEffect(() => {
    if (!canvasRef.current) return;
    chartRef.current = new Chart(canvasRef.current, {
      type: 'line',
      data: {
        datasets: [{
          label: 'Live Value',
          data: [],         // filled below
          borderWidth: 2,
          fill: false,
        }]
      },
      options: {
        animation: false,
        scales: {
          x: {
            type: 'linear',               // numeric axis
            title: { display: true, text: 'Iteration' },
            ticks: { stepSize: 1 }        // one tick per iteration
          },
          y: {
            min: 0, max: 1,
            title: { display: true, text: 'Value' }
          }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });
    return () => { chartRef.current?.destroy(); };
  }, []);

  // 2️⃣ When `data` updates, map to {x: idx+1, y: value}
  useEffect(() => {
    const ch = chartRef.current;
    if (!ch) return;
    ch.data.datasets![0].data = data.map((pt, i) => ({
      x: i + 1,
      y: pt.value
    }));
    ch.update('none');
  }, [data]);

  // 3️⃣ Poll your backend every second
  useEffect(() => {
    let cancelled = false;
    const fetchData = async () => {
      const res = await fetch('/python-stream/data');
      if (res.ok && !cancelled) {
        setData(await res.json());
      }
    };
    fetchData();
    const id = setInterval(fetchData, 1000);
    return () => {
      cancelled = true;
      clearInterval(id);
    };
  }, []);

  return (
    <div className="flex-grow bg-[#fff] dark:bg-[#111] rounded-sm p-4">
      <canvas ref={canvasRef} />
    </div>
  );
}
