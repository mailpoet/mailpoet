#!/bin/bash -e

echo "Getting translations from Transifex..."
tx pull -a -f --parallel

echo "Generating MO files..."
for file in `find ./lang/ -name "*.po"` ; do
  msgfmt -o ${file/.po/.mo} $file
done
echo "Done"
