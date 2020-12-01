# Gutenberg Toggle

Wordpress Plugin for turning block editor on and off per post


## Toolchain for Plugin Dev

```
sudo apt install nodejs npm
npm install npm@latest -g
npm init
npm install @wordpress/scripts --save-dev
npm install @wordpress/edit-post @wordpress/plugins @wordpress/i18n @wordpress/components @wordpress/data @wordpress/compose @wordpress/element --save
```
Replace the scripts entry in package.json with the following:
```
"scripts": {
  "start": "wp-scripts start",
  "build": "wp-scripts build"
},
```
During development use `npm start` to keep javascript up-to-date. Before packaging, run `npm run build` to generate minified code.

## Helpful tutorials

  * https://www.youtube.com/watch?v=mi8kpAgHYFo
  * https://css-tricks.com/managing-wordpress-metadata-in-gutenberg-using-a-sidebar-plugin/
  * https://www.hostinger.com/tutorials/run-docker-wordpress
