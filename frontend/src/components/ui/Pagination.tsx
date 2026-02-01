import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';

export interface PaginationProps {
  page: number;
  limit: number;
  total: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  onLimitChange?: (limit: number) => void;
  limitOptions?: number[];
}

export function Pagination({
  page,
  limit,
  total,
  totalPages,
  onPageChange,
  onLimitChange,
  limitOptions = [10, 20, 50],
}: PaginationProps) {
  if (totalPages <= 1 && total <= (limitOptions[0] ?? 10)) return null;

  const start = total === 0 ? 0 : (page - 1) * limit + 1;
  const end = Math.min(page * limit, total);

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50/80 dark:bg-zinc-800/80 px-4 py-3 sm:px-6 rounded-b-xl">
      <div className="flex flex-wrap items-center gap-4">
        <p className="text-sm text-zinc-600 dark:text-zinc-400">
          Toplam <span className="font-medium text-zinc-900 dark:text-white">{total}</span> kayıttan{' '}
          <span className="font-medium text-zinc-900 dark:text-white">{start}</span>–<span className="font-medium text-zinc-900 dark:text-white">{end}</span>
        </p>
        {onLimitChange && (
          <div className="flex items-center gap-2">
            <label htmlFor="pagination-limit" className="text-sm text-zinc-600 dark:text-zinc-400">
              Sayfa başına:
            </label>
            <select
              id="pagination-limit"
              value={limit}
              onChange={(e) => onLimitChange(Number(e.target.value))}
              className="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-2 py-1.5 text-sm text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
            >
              {limitOptions.map((n) => (
                <option key={n} value={n}>
                  {n}
                </option>
              ))}
            </select>
          </div>
        )}
      </div>
      <div className="flex items-center gap-1">
        <button
          type="button"
          onClick={() => onPageChange(page - 1)}
          disabled={page <= 1}
          className="inline-flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 p-2 text-zinc-700 dark:text-zinc-200 shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:cursor-not-allowed disabled:opacity-50"
          aria-label="Önceki sayfa"
        >
          <ChevronLeftIcon className="h-5 w-5" />
        </button>
        <span className="px-3 text-sm text-zinc-600 dark:text-zinc-400">
          Sayfa <span className="font-medium">{page}</span> / <span className="font-medium">{totalPages || 1}</span>
        </span>
        <button
          type="button"
          onClick={() => onPageChange(page + 1)}
          disabled={page >= totalPages}
          className="inline-flex items-center rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 p-2 text-zinc-700 dark:text-zinc-200 shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:cursor-not-allowed disabled:opacity-50"
          aria-label="Sonraki sayfa"
        >
          <ChevronRightIcon className="h-5 w-5" />
        </button>
      </div>
    </div>
  );
}
