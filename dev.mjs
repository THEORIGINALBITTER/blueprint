import { spawn } from 'node:child_process';

const css = spawn('npx', ['@tailwindcss/cli', '-i', './src/tailwind.css', '-o', './public.css', '--watch'], { stdio: 'inherit', shell: process.platform === 'win32' });
const server = spawn(process.execPath, ['server.mjs'], { stdio: 'inherit' });

const stop = () => { css.kill('SIGTERM'); server.kill('SIGTERM'); };
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
server.on('exit', code => process.exit(code ?? 0));
