import { readFileSync } from 'node:fs';
import { classifyPdf, extractPagesMarkdown } from '@firecrawl/pdf-inspector';

const [, , command, pdfPath] = process.argv;

if (!command || !pdfPath) {
  console.error('Usage: node scripts/pdf-inspector-bridge.mjs <extract|classify> <pdf_file>');
  process.exit(1);
}

const pdf = readFileSync(pdfPath);

if (command === 'extract') {
  const result = extractPagesMarkdown(pdf);
  const markdown = result.pages
    .map((page) => page.markdown)
    .filter(Boolean)
    .join('\n\n');

  process.stdout.write(markdown);
} else if (command === 'classify') {
  process.stdout.write(JSON.stringify(classifyPdf(pdf)));
} else {
  console.error(`Unknown command: ${command}`);
  process.exit(1);
}
