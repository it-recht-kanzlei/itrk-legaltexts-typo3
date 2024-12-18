#!/bin/bash
# apt-get install pandoc lmodern texlive-latex-recommended texlive-lang-german texlive-latex-extra

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

cd "$SCRIPT_DIR/.."

find . -name "*.md" -print0 | while read -d $'\0' file
do
    echo "Processing " "$file"

    pandoc "$file" \
        -o "${file%.md}.pdf" \
        -s \
        --listings \
        --lua-filter="$SCRIPT_DIR/promote-headers.lua" \
        --template="$SCRIPT_DIR/template.tex"
done
