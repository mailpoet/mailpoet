#!/bin/bash

echo "Getting translations from Transifex..."
tx pull -a -f

echo "Generating MO files..."
for file in `find ./lang/ -name "*.po"` ; do
  msgfmt -o ${file/.po/.mo} $file
done
echo "Done"
