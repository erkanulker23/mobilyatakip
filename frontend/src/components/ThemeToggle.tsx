import { SunIcon, MoonIcon } from '@heroicons/react/24/outline';
import { useThemeStore } from '../stores/themeStore';

export default function ThemeToggle() {
  const { theme, toggleTheme } = useThemeStore();

  return (
    <button
      type="button"
      onClick={toggleTheme}
      className="flex items-center gap-2 px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors text-sm font-medium"
      title={theme === 'light' ? 'Koyu mod' : 'Açık mod'}
      aria-label={theme === 'light' ? 'Koyu moda geç' : 'Açık moda geç'}
    >
      {theme === 'light' ? (
        <>
          <MoonIcon className="w-5 h-5" />
          <span className="hidden sm:inline">Koyu mod</span>
        </>
      ) : (
        <>
          <SunIcon className="w-5 h-5" />
          <span className="hidden sm:inline">Açık mod</span>
        </>
      )}
    </button>
  );
}
