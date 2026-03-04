#!/usr/bin/env node

/**
 * Project Analyzer - ESM entry point.
 * Run via: node .claude/tools/project-analyzer/main.cjs [--output path]
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

function findProjectRoot() {
  let dir = process.cwd();
  const root = path.parse(dir).root;
  while (dir !== root) {
    if (fs.existsSync(path.join(dir, '.claude')) || fs.existsSync(path.join(dir, 'composer.json')))
      return dir;
    dir = path.dirname(dir);
  }
  return process.cwd();
}

const PROJECT_ROOT = findProjectRoot();
const args = process.argv.slice(2);
const outputIndex = args.indexOf('--output');
const outputPath = outputIndex >= 0 && args[outputIndex + 1] ? args[outputIndex + 1] : null;

function collectStats() {
  const stats = { total_files: 0, total_lines: 0, languages: {}, file_types: {}, directories: 0 };
  const skip = new Set(['node_modules', 'vendor', '.git', 'dist', 'build', 'storage/framework', 'bootstrap/cache']);
  const extToLang = { '.php': 'PHP', '.js': 'JavaScript', '.mjs': 'JavaScript', '.ts': 'TypeScript', '.tsx': 'TypeScript', '.vue': 'Vue', '.blade.php': 'Blade', '.json': 'JSON', '.md': 'Markdown', '.css': 'CSS', '.yaml': 'YAML', '.yml': 'YAML' };

  function walk(dir) {
    if (!fs.existsSync(dir)) return;
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const e of entries) {
      const full = path.join(dir, e.name);
      const rel = path.relative(PROJECT_ROOT, full);
      if (rel.split(path.sep).some(part => skip.has(part))) continue;
      if (e.isDirectory()) {
        stats.directories++;
        walk(full);
      } else {
        stats.total_files++;
        const ext = path.extname(e.name);
        const lang = extToLang[ext] || ext || 'Other';
        stats.languages[lang] = (stats.languages[lang] || 0) + 1;
        stats.file_types[ext || '(none)'] = (stats.file_types[ext || '(none)'] || 0) + 1;
        try {
          const content = fs.readFileSync(full, 'utf8');
          stats.total_lines += content.split(/\r?\n/).length;
        } catch (_) {}
      }
    }
  }

  walk(PROJECT_ROOT);
  return stats;
}

function detectFrameworks() {
  const frameworks = [];
  const composerPath = path.join(PROJECT_ROOT, 'composer.json');
  const packagePath = path.join(PROJECT_ROOT, 'package.json');
  if (fs.existsSync(composerPath)) {
    try {
      const composer = JSON.parse(fs.readFileSync(composerPath, 'utf8'));
      const deps = { ...composer.require, ...(composer['require-dev'] || {}) };
      if (deps['laravel/framework']) frameworks.push({ name: 'laravel', version: deps['laravel/framework'], category: 'framework', confidence: 1.0, source: 'composer.json' });
      if (deps['livewire/livewire']) frameworks.push({ name: 'livewire', version: deps['livewire/livewire'], category: 'framework', confidence: 1.0, source: 'composer.json' });
    } catch (_) {}
  }
  if (fs.existsSync(packagePath)) {
    try {
      const pkg = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
      const deps = { ...pkg.dependencies, ...(pkg.devDependencies || {}) };
      if (deps['tailwindcss']) frameworks.push({ name: 'tailwindcss', version: deps['tailwindcss'], category: 'css', confidence: 1.0, source: 'package.json' });
      if (deps['vite']) frameworks.push({ name: 'vite', version: deps['vite'], category: 'build', confidence: 1.0, source: 'package.json' });
    } catch (_) {}
  }
  return frameworks;
}

function run() {
  const projectType = fs.existsSync(path.join(PROJECT_ROOT, 'artisan')) ? 'backend' : (fs.existsSync(path.join(PROJECT_ROOT, 'package.json')) ? 'frontend' : 'fullstack');
  const stats = collectStats();
  const frameworks = detectFrameworks();
  const result = {
    analysis_id: `analysis-${path.basename(PROJECT_ROOT)}-${Date.now()}`,
    project_type: projectType,
    analyzed_at: new Date().toISOString(),
    project_root: PROJECT_ROOT,
    stats: { ...stats, avg_file_size_lines: stats.total_files ? Math.round(stats.total_lines / stats.total_files) : 0 },
    frameworks,
    structure: { architecture_pattern: 'modular', module_system: 'esm' },
    metadata: { analyzer_version: '1.0.0', files_analyzed: stats.total_files, errors: [] },
  };
  const out = { success: true, result };
  const json = JSON.stringify(out, null, 2);
  if (outputPath) {
    const abs = path.isAbsolute(outputPath) ? outputPath : path.join(PROJECT_ROOT, outputPath);
    fs.mkdirSync(path.dirname(abs), { recursive: true });
    fs.writeFileSync(abs, json, 'utf8');
    console.log('Wrote:', abs);
  } else {
    console.log(json);
  }
}

try {
  run();
} catch (err) {
  const out = { success: false, error: err.message };
  console.error(JSON.stringify(out, null, 2));
  process.exit(1);
}
