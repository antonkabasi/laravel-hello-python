import { useState } from 'react';

interface Props {
  /** e.g. '/hello-python' returns `{ output: string }`,
   *  or '/plot-sine' returns `{ img: string }` */
  endpoint: string;
  /** if true, expect `{ img: string }` and render `<img>`, otherwise `{ output: string }` in `<pre>` */
  isImage?: boolean;
}

export default function PythonRunner({ endpoint, isImage = false }: Props) {
  const [data, setData] = useState<string>('');
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string>('');

  const run = async () => {
    setLoading(true);
    setData('');
    setError('');
    try {
      const res = await fetch(endpoint);
      const json = await res.json();
      if (!res.ok || json.error) {
        const msg = json.error || `Error ${res.status}: ${res.statusText}`;
        throw new Error(msg);
      }
      const result = isImage ? json.img : json.output;
      if (!result) throw new Error('Invalid response format');
      setData(result);
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
        className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b] disabled:opacity-50"
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
          className="mt-4 p-4 bg-[#f0f0f0] dark:bg-[#222] dark:text-white rounded-sm text-[13px] leading-[20px] whitespace-pre-wrap"
        >
          {error ? error : data || ' '}
        </pre>
      )}
    </div>
  );
}
