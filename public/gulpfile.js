/**
 * Created by Zix on 16/4/29.
 */
var gulp = require('gulp');
var cleanCSS = require('gulp-clean-css');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');

gulp.task('global-app-css' , function () {
	var style_css = [
		'static/src/themes/global/components-rounded.css' ,
		'static/src/themes/global/layout.css' ,
		'static/src/themes/global/darkblue.css' ,
	];
	return gulp.src(style_css) //压缩的文件
	           .pipe(concat('app.css'))    //合并所有css到style.css
	           .pipe(rename({suffix : '.min'}))   //rename压缩后的文件名
	           .pipe(cleanCSS({compatibility : 'ie8'})) //执行压缩
	           .pipe(gulp.dest('static/themes/global')); //输出文件夹
});

gulp.task('global-app-js' , function () {
	var layout_js = [
		'static/src/js/global/app.js' ,
		'static/src/js/global/layout.js'
	];
	return gulp.src(layout_js) //压缩的文件
	           .pipe(concat('app.js'))    //合并所有css到style.css
	           .pipe(rename({suffix : '.min'}))   //rename压缩后的文件名
	           .pipe(uglify())    //压缩
	           .pipe(gulp.dest('static/js/global')); //输出文件夹
});


gulp.task('global-custom-css' , function () {
	var custom_css = [
		'static/src/themes/global/custom.css' ,
		'static/src/themes/global/plugins.css' ,
		'node_modules/toastr/build/toastr.css'
	];
	return gulp.src(custom_css) //压缩的文件
	           .pipe(concat('custom.css'))    //合并所有css到style.css
	           .pipe(rename({suffix : '.min'}))   //rename压缩后的文件名
	           .pipe(cleanCSS({compatibility : 'ie8'})) //执行压缩
	           .pipe(gulp.dest('static/themes/global')); //输出文件夹
});


gulp.task('global-custom-js' , function () {
	var custom_js = [
		'node_modules/toastr/toastr.js' ,
		'static/src/js/global/custom.js' ,
		'static/src/js/global/format.js'
	];
	return gulp.src(custom_js) //压缩的文件
	           .pipe(concat('custom.js'))    //合并所有css到style.css
	           .pipe(rename({suffix : '.min'}))   //rename压缩后的文件名
	           .pipe(uglify())    //压缩
	           .pipe(gulp.dest('static/js/global')); //输出文件夹
});


gulp.task('default' , function () {
	gulp.start('global-app-css' , 'global-app-js' , 'global-custom-css' , 'global-custom-js');
});