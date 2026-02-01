import type { ReactNode } from 'react';

interface EmptyStateProps {
  icon?: React.ComponentType<{ className?: string }>;
  title: string;
  description?: string;
  action?: ReactNode;
  className?: string;
}

export function EmptyState({ icon: Icon, title, description, action, className = '' }: EmptyStateProps) {
  return (
    <div
      className={`flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/50 py-16 px-6 text-center ${className}`}
    >
      {Icon && (
        <span className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500">
          <Icon className="h-8 w-8" />
        </span>
      )}
      <h3 className="text-base font-semibold text-zinc-700 dark:text-zinc-200">{title}</h3>
      {description && <p className="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{description}</p>}
      {action && <div className="mt-6">{action}</div>}
    </div>
  );
}
