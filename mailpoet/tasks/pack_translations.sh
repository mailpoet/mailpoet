#!/bin/bash -e

BASEDIR=$(dirname "$0")
TX=$BASEDIR/../tools/transifex.php

echo "Getting translations from Transifex..."
php "$TX" pull -a -f

echo "Generating MO files..."
for file in `find ./lang/ -name "*.po"` ; do
  msgfmt -o ${file/.po/.mo} $file
done

echo "Creating formal translations..."
if [[ -f ./lang/mailpoet-de.mo && -f ./lang/mailpoet-de.po ]]; then
	mv ./lang/mailpoet-de.mo ./lang/mailpoet-de_DE_formal.mo
	mv ./lang/mailpoet-de.po ./lang/mailpoet-de_DE_formal.po
fi
if [[ -f ./lang/mailpoet-nl.mo && -f ./lang/mailpoet-nl.po ]]; then
	mv ./lang/mailpoet-nl.mo ./lang/mailpoet-nl_NL_formal.mo
	mv ./lang/mailpoet-nl.po ./lang/mailpoet-nl_NL_formal.po
fi

echo "Done"
