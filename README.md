# Slugify for Alfred 3
Alfred 3 workflow to turn any text into a url compatible string of characters. Based on WordPress' function to create URL slugs.

## Installation
Just download the zip [here](https://github.com/david-treblig/slugify-alfred-3/blob/master/Slugify.alfredworkflow?raw=true) and open the worfklow file with Alfred 3.

## Instructions
Type ```slug ANYTHING THAT YOU WANT, even with accents like éàê```

And this workflow will turn it into ```anything-you-that-you-want-even-with-accents-like-eae```.

This workflow is very useful to rename files quickly as well with the provided file action (cmd + shift + space is the usual shortcut for this) where all selected files in the Finder will be processed by the script.

## To do
Eventually move the file action to an output of AppleScript or other script that commits to Finder's history to allow going back (cmd + z) and undo the rename action.