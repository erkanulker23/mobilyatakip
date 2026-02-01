import { PencilSquareIcon, TrashIcon, EyeIcon } from '@heroicons/react/24/outline';
import { Link } from 'react-router-dom';

const btnBase = 'inline-flex items-center justify-center gap-2 rounded-lg p-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50';

interface ActionButtonsProps {
  onEdit?: () => void;
  onDelete?: () => void;
  viewHref?: string;
  viewLabel?: string;
  deleteLabel?: string;
  editLabel?: string;
  disabled?: boolean;
  /** Sadece ikon (metin yok) */
  iconOnly?: boolean;
}

export function ActionButtons({
  onEdit,
  onDelete,
  viewHref,
  viewLabel = 'Görüntüle',
  deleteLabel = 'Sil',
  editLabel = 'Düzenle',
  disabled,
  iconOnly = true,
}: ActionButtonsProps) {
  return (
    <div className="flex items-center justify-end gap-1">
      {viewHref && (
        iconOnly ? (
          <Link
            to={viewHref}
            className={`${btnBase} text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 focus:ring-emerald-500`}
            title={viewLabel}
          >
            <EyeIcon className="h-5 w-5" />
          </Link>
        ) : (
          <Link
            to={viewHref}
            className={`${btnBase} text-zinc-600 dark:text-zinc-300 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 focus:ring-emerald-500`}
          >
            <EyeIcon className="h-4 w-4" />
            {viewLabel}
          </Link>
        )
      )}
      {onEdit && (
        <button
          type="button"
          onClick={onEdit}
          disabled={disabled}
          title={editLabel}
          className={`${btnBase} text-zinc-500 dark:text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 focus:ring-amber-500`}
        >
          <PencilSquareIcon className="h-5 w-5" />
          {!iconOnly && <span>{editLabel}</span>}
        </button>
      )}
      {onDelete && (
        <button
          type="button"
          onClick={onDelete}
          disabled={disabled}
          title={deleteLabel}
          className={`${btnBase} text-zinc-500 dark:text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 focus:ring-red-500`}
        >
          <TrashIcon className="h-5 w-5" />
          {!iconOnly && <span>{deleteLabel}</span>}
        </button>
      )}
    </div>
  );
}
