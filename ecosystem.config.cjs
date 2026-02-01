/**
 * PM2 ecosystem config — Mobilya Takip Backend
 * Kullanım: pm2 start ecosystem.config.cjs
 * Log: pm2 logs | pm2 logs mobilyatakip
 * Restart: pm2 restart mobilyatakip
 */
module.exports = {
  apps: [
    {
      name: 'mobilyatakip',
      script: 'dist/main.js',
      cwd: __dirname,
      instances: 1,
      exec_mode: 'fork',
      autorestart: true,
      watch: false,
      max_memory_restart: '500M',
      env: {
        NODE_ENV: 'development',
      },
      env_production: {
        NODE_ENV: 'production',
      },
      merge_logs: true,
      time: true,
    },
  ],
};
