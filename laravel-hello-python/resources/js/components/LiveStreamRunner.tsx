// resources/js/Components/LiveStreamRunner.tsx
import { useEffect, useState, useRef } from 'react';
import Chart from 'chart.js/auto';

function getCsrf() {
  const m = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
  if (!m) throw new Error('CSRF token not found');
  return m.content;
}

export default function LiveStreamRunner() {
  const [running, setRunning]   = useState(false);
  const [timeLeft, setTimeLeft] = useState(0);
  const [data, setData] = useState<{ id:number; timestamp:string; value:number }[]>([]);

  const evtRef    = useRef<EventSource|null>(null);
  const timerRef  = useRef<number|null>(null);
  const chartRef  = useRef<HTMLCanvasElement>(null);
  const chartInst = useRef<Chart|null>(null);

  // 1) Init Chart.js
  useEffect(() => {
    if (!chartRef.current) return;
    chartInst.current = new Chart(chartRef.current, {
      type: 'line',
      data: {
        datasets:[{
          label:'Value',
          data: [],
          borderWidth:2,
          fill:false,
          tension:0.3
        }]
      },
      options: {
        animation:false,
        scales: {
          x: {
            type: 'linear',
            title: { display:true, text:'Sample #' },
          },
          y: {
            min:0, max:1,
            title: { display:true, text:'Value' }
          }
        },
        plugins:{ legend:{ display:false }}
      }
    });
    return ()=>{ chartInst.current?.destroy(); };
  }, []);

  // 2) Load full history once
  useEffect(() => {
    fetch('/python-stream/history')
      .then(r => r.json())
      .then((rows) => {
        setData(rows);
        const ch = chartInst.current!;
        ch.data.datasets![0].data = rows.map((r:any)=>({ x:r.id, y:r.value }));
        ch.update('none');
      });
  }, []);

  // 3) Push into chart on data change
  useEffect(() => {
    const ch = chartInst.current;
    if (!ch) return;
    ch.data.datasets![0].data = data.map(d=>({ x:d.id, y:d.value }));
    ch.update('none');
  }, [data]);

  // start streaming + countdown
  const start = async () => {
    setRunning(true);
    setTimeLeft(10);

    // launch python writer
    await fetch('/python-stream/start', {
      method:'POST',
      headers:{ 'X-CSRF-TOKEN':getCsrf() }
    });

    // open SSE
    const src = new EventSource('/python-stream/stream');
    evtRef.current = src;
    src.onmessage = e => {
      const row = JSON.parse(e.data);
      setData(d => [...d.slice(-299), row]);
    };

    // countdown
    timerRef.current = window.setInterval(() => {
      setTimeLeft(t => {
        if (t <= 1) {
          stopAll();
          return 0;
        }
        return t - 1;
      });
    }, 1000);
  };

  // stop both SSE + timer + Python
  const stopAll = () => {
    // clear countdown
    if (timerRef.current !== null) {
      clearInterval(timerRef.current);
      timerRef.current = null;
    }

    // close SSE
    evtRef.current?.close();
    evtRef.current = null;

    // kill python writer
    fetch('/python-stream/stop', {
      method:'POST',
      headers:{ 'X-CSRF-TOKEN':getCsrf() }
    });

    setRunning(false);
    setTimeLeft(0);
  };

  // cleanup on unmount
  useEffect(() => {
    return () => { stopAll(); };
  }, []);

  return (
    <div className="w-full max-w-full lg:max-w-4xl mb-6 flex space-x-4">
      <div className="flex-shrink-0">
        <button
          onClick={start}
          disabled={running}
          className="inline-block rounded-sm border border-[#19140035]
                     px-5 py-1.5 text-sm leading-normal text-[#1b1b18]
                     hover:border-[#1915014a] dark:border-[#3E3E3A]
                     dark:text-[#EDEDEC] dark:hover:border-[#62605b]
                     disabled:opacity-50"
        >
          {running ? `Streaming… (${timeLeft}s)` : 'Start Stream'}
        </button>

        <button
          onClick={stopAll}
          disabled={!running}
          className="ml-2 inline-block rounded-sm border border-[#19140035]
                     px-5 py-1.5 text-sm leading-normal text-[#1b1b18]
                     hover:border-[#1915014a] dark:border-[#3E3E3A]
                     dark:text-[#EDEDEC] dark:hover:border-[#62605b]
                     disabled:opacity-50"
        >
          Stop
        </button>

        <div className="mt-4 p-4 bg-[#f0f0f0] dark:bg-[#222]
                        rounded-sm h-64 overflow-y-auto">
          <ul className="space-y-1 text-sm font-mono text-black dark:text-white">
            {data.map(d => (
              <li key={d.id}>#{d.id}: {d.value.toFixed(4)}</li>
            ))}
          </ul>
        </div>
      </div>

      <div className="flex-grow bg-[#fff] dark:bg-[#111] rounded-sm p-4">
        <canvas ref={chartRef} />
      </div>
    </div>
  );
}
