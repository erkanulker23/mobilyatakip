interface StatCardProps {
  title: string;
  value: string | number;
  icon?: React.ComponentType<{ className?: string }>;
  description?: string;
  trend?: 'up' | 'down' | 'neutral';
  href?: string;
  className?: string;
}

export function StatCard({ title, value, icon: Icon, description, trend, href, className = '' }: StatCardProps) {
  const Wrapper = href ? 'a' : 'div';
  const content = (
    <>
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm font-medium text-zinc-500 dark:text-zinc-400">{title}</p>
          <p className="mt-1 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">{value}</p>
          {description && <p className="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{description}</p>}
        </div>
        {Icon && (
          <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">
            <Icon className="h-6 w-6" />
          </span>
        )}
      </div>
      {trend && (
        <div className="mt-3 flex items-center gap-1 text-xs font-medium">
          {trend === 'up' && <span className="text-emerald-600 dark:text-emerald-400">↑ Artış</span>}
          {trend === 'down' && <span className="text-amber-600 dark:text-amber-400">↓ Azalış</span>}
          {trend === 'neutral' && <span className="text-zinc-400 dark:text-zinc-500">— Sabit</span>}
        </div>
      )}
    </>
  );

  const baseClass =
    'rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-lg shadow-zinc-200/50 dark:shadow-black/20 transition hover:shadow-xl hover:border-zinc-300/80 dark:hover:border-zinc-600/80';

  if (Wrapper === 'a') {
    return (
      <a href={href} className={`block ${baseClass} ${className}`}>
        {content}
      </a>
    );
  }
  return <div className={`${baseClass} ${className}`}>{content}</div>;
}
