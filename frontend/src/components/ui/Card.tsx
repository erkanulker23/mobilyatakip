import type { ReactNode } from 'react';

interface CardProps {
  children: ReactNode;
  className?: string;
  padding?: 'none' | 'sm' | 'md' | 'lg';
}

export function Card({ children, className = '', padding = 'md' }: CardProps) {
  const paddingClass =
    padding === 'none'
      ? ''
      : padding === 'sm'
        ? 'p-4'
        : padding === 'lg'
          ? 'p-8'
          : 'p-6';
  return (
    <div
      className={`rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 shadow-lg shadow-zinc-200/50 dark:shadow-black/20 ${paddingClass} ${className}`}
    >
      {children}
    </div>
  );
}

interface CardTitleProps {
  children: ReactNode;
  icon?: React.ComponentType<{ className?: string }>;
  className?: string;
}

export function CardTitle({ children, icon: Icon, className = '' }: CardTitleProps) {
  return (
    <div className={`mb-4 flex items-center gap-2 ${className}`}>
      {Icon && (
        <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
          <Icon className="h-5 w-5" />
        </span>
      )}
      <h2 className="text-lg font-semibold text-zinc-900 dark:text-white">{children}</h2>
    </div>
  );
}
