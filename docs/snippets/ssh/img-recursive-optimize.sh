#!/bin/bash
optimize() {
	jpegoptim *.jpg --strip-all --all-progressive -t -m80 # -n to test
	optipng *.png     -strip all #from 0.7.4 -simulate - to test
	for i in *
	do
		if test -d $i
		then
			cd $i
			echo $i
			optimize
			cd ..
		fi
	done
	echo
}
optimize