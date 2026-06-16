#!/bin/bash

# Parse `.gitmodules` file from another project and use its submodules in this one

[ ! -f ./.gitmodules ] && echo "No ./.gitmodules file to parse!" && exit 1

git config -f .gitmodules -l|grep url|sed -E "s%submodule\.(.*)\.url=(.*)%\1 \2%g"|while read MODULEPATH URL; do
    git submodule add $URL $MODULEPATH
done

git add ./.gitmodules
git status

echo "Commit the changes above!"
