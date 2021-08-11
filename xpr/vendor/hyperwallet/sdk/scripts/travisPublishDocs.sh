#!/bin/sh

# Set identity
git config --global user.email "travis@travis-ci.org"
git config --global user.name "Travis"

# Build and publish docs
sh ./scripts/generateDocs.sh

mkdir ../gh-pages
cp -r docs/* ../gh-pages/
cd ../gh-pages

# Add branch
git init
git remote add origin https://${GH_TOKEN}@github.com/hyperwallet/php-sdk.git > /dev/null
git checkout -B gh-pages

# Push generated files
git add .
git commit -m "Documentation updated"
git push origin gh-pages -fq > /dev/null
