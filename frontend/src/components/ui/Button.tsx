import type { ReactNode } from 'react';

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';

interface ButtonProps {
  children: ReactNode;
  variant?: ButtonVariant;
  type?: 'button' | 'submit' | 'reset';
  onClick?: () => void;
  disabled?: boolean;
  className?: string;
  icon?: React.ComponentType<{ className?: string }>;
}

const variantClasses: Record<ButtonVariant, string> = {
  primary:
    'bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 focus:ring-emerald-500 border-transparent dark:bg-emerald-600 dark:hover:bg-emerald-700',
  secondary:
    'border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-600 focus:ring-zinc-500',
  ghost:
    'text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 focus:ring-zinc-500 border-transparent',
  danger:
    'bg-red-600 text-white shadow-sm hover:bg-red-700 focus:ring-red-500 border-transparent dark:bg-red-600 dark:hover:bg-red-700',
};

export function Button({
  children,
  variant = 'primary',
  type = 'button',
  onClick,
  disabled,
  className = '',
  icon: Icon,
}: ButtonProps) {
  const base =
    'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-zinc-800 disabled:opacity-50 disabled:pointer-events-none';
  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      className={`${base} ${variantClasses[variant]} ${className}`}
    >
      {Icon && <Icon className="h-5 w-5 shrink-0" />}
      {children}
    </button>
  );
}
