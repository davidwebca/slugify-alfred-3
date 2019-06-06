# Slugify for Alfred 3 and 4
Alfred 3+ workflow to turn any text into a url compatible string of characters. Based on WordPress' function to create URL slugs.

## Installation
Just download the zip [here](https://github.com/david-treblig/slugify-alfred-3/blob/master/Slugify.alfredworkflow?raw=true) and open the worfklow file with Alfred.

## Instructions
Type ```slug ANYTHING THAT YOU WANT, even with accents like éàê```

And this workflow will turn it into ```anything-you-that-you-want-even-with-accents-like-eae```.

## Batch renaming files
This workflow is very useful to rename files quickly as well. 

Just select multiple files, hit cmd + shift + space (this is the usual shortcut for file actions, yours can be different) and type "Slug", then select the provided file action. All selected files in the Finder will be processed by the script and be renamed for a URL safe environment.

## To do
Eventually move the file action to an output of AppleScript or other script that commits to Finder's history to allow going back (cmd + z) and undo the rename action.
