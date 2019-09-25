#!/bin/bash -e

echo "Getting translations from Transifex..."
tx pull -a -f --parallel

echo "Generating MO files..."
for file in `find ./lang/ -name "*.po"` ; do
  msgfmt -o ${file/.po/.mo} $file
done

echo "Creating german formal translation..."
if [[ -f ./lang/mailpoet-de.mo && -f ./lang/mailpoet-de.po ]]; then
	mv ./lang/mailpoet-de.mo ./lang/mailpoet-de_DE-formal.mo
	mv ./lang/mailpoet-de.po ./lang/mailpoet-de_DE-formal.po
fi

echo "Done"
