#!/bin/bash
# Generate AI-ready codebase digest using GitIngest
# Usage: ./scripts/gitingest.sh [output-path]
#
# The .gitingest.toml config in project root controls what's included/excluded.
# Output is gitignored — never commit digests (they contain source code).

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUTPUT="${1:-/tmp/kalfa-digest.txt}"

echo "📦 Generating codebase digest..."
echo "   Project: $PROJECT_ROOT"
echo "   Output:  $OUTPUT"
echo ""

cd "$PROJECT_ROOT"

# Check if gitingest is installed
if ! command -v gitingest &> /dev/null; then
    echo "gitingest not found. Install with: pipx install gitingest"
    exit 1
fi

gitingest . -o "$OUTPUT"

# Show stats
if [ -f "$OUTPUT" ]; then
    SIZE=$(du -h "$OUTPUT" | cut -f1)
    LINES=$(wc -l < "$OUTPUT")
    echo ""
    echo "Done! $SIZE, $LINES lines"
    echo "Token estimate: ~$(( LINES * 3 )) tokens"
fi
