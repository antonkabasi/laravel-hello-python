// resources/js/Components/PythonRunner.tsx
import { useState } from 'react';

interface Props {
  /** e.g. 'hello.py' or 'plot_sine.py' (no leading slash) */
  script: string;
  /** if true, expect `{ stdout: string }` contains base64 GIF */
  isImage?: boolean;
}

function getCsrf() {
  const m = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
  if (!m) throw new Error('CSRF token not found');
  return m.content;
}

export default function PythonRunner({ script, isImage = false }: Props) {
  const [data, setData]       = useState<string>('');
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError]     = useState<string>('');

  const run = async () => {
    setLoading(true);
    setData('');
    setError('');
    try {
      // 1) Dispatch the job
      const dispatchRes = await fetch(`/python/dispatch/${script}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf() }
      });
      const { uuid } = await dispatchRes.json();
      if (!dispatchRes.ok || !uuid) {
        throw new Error(dispatchRes.statusText || 'Dispatch failed');
      }

      // 2) Poll until done
      let status = 'running';
      while (status === 'running') {
        await new Promise(r => setTimeout(r, 500));
        const statusRes = await fetch(`/python/status/${uuid}`);
        const json = await statusRes.json();
        if (!statusRes.ok) {
          throw new Error(json.error || statusRes.statusText);
        }
        status = json.status;
        if (status === 'done') {
          if (json.stderr) {
            throw new Error(json.stderr);
          }
          setData(json.stdout as string);
        }
      }
    } catch (e: any) {
      setError(e.message || 'Unknown error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="w-full max-w-full lg:max-w-4xl mb-6">
      <button
        onClick={run}
        disabled={loading}
        className="inline-block rounded-sm border border-[#19140035]
                   px-5 py-1.5 text-sm leading-normal text-[#1b1b18]
                   hover:border-[#1915014a] dark:border-[#3E3E3A]
                   dark:text-[#EDEDEC] dark:hover:border-[#62605b]
                   disabled:opacity-50"
      >
        {loading
          ? 'Running…'
          : isImage
            ? 'Generate Sine Plot'
            : 'Hello, Python?'}
      </button>

      {error && (
        <p className="mt-2 text-red-600 dark:text-red-400 text-sm">{error}</p>
      )}

      {isImage ? (
        <div className="mt-4 w-full h-[350px] relative rounded-sm border bg-[#fff] dark:bg-[#111] overflow-visible pb-6">
          {loading && !error && (
            <p className="absolute inset-0 flex items-center justify-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
              Generating plot…
            </p>
          )}
          {!loading && data && (
            <img
              src={`data:image/gif;base64,${data}`}
              alt="Python-generated sine plot"
              className="absolute inset-0 w-full h-full object-contain"
            />
          )}
        </div>
      ) : (
        <pre
          id="python-output"
          className="mt-4 p-4 bg-[#f0f0f0] dark:bg-[#222] dark:text-white
                     rounded-sm text-[13px] leading-[20px] whitespace-pre-wrap"
        >
          {error ? error : data || ' '}
        </pre>
      )}
    </div>
  );
}
