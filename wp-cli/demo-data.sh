#!/bin/bash

SIZE="$1"

if [ "$SIZE" = "small" ] ; then

	printf "\nGenerating Customers...\n"
	wp itelic generate-customers --count=100 --billing

	printf "\nGenerating Products...\n"
	wp itelic product generate --count=10

	printf "\nGenerating Keys...\n"
	wp itelic key generate  --activations

	printf "\nGenerating Renewals...\n"
	wp itelic renewal generate 33


elif [ "$SIZE" = "medium" ] ; then

	printf "\nGenerating Customers...\n"
	wp itelic generate-customers --count=500 --billing

	printf "\nGenerating products...\n"
	wp itelic product generate --count=20

	printf "\nGenearting Keys...\n"

	for (( i = 1; i <= 10 ; i++ )) ; do
		wp itelic key generate --activations
	done

	printf "\nGenerating Renewals...\n"
	wp itelic renewal generate 33

elif [ "$SIZE" = "large" ] ; then

	printf "\nGenerating Customers...\n"
	wp itelic generate-customers --count=1000 --billing
	wp itelic generate-customers --count=1000 --billing

	printf "\nGenerating products...\n"
	wp itelic product generate --count=50

	printf "\nGenearting Keys...\n"

    for (( i = 1; i <= 40 ; i++ )) ; do
    	wp itelic key generate --activations
    done

	pritnf "\nGenerating Renewals...\n"
	wp itelic renewal generate 33

elif [ "$SIZE" = "giant" ] ; then

	printf "\nGenerating Customers...\n"
	
    for (( i = 1; i <= 5 ; i++ )) ; do
		wp itelic generate-customers --count=1000 --billing
    done

	printf "\nGenerating products...\n"
	wp itelic product generate --count=150

	 printf "\nGenearting Keys...\n"

        for (( i = 1; i <= 100 ; i++ )) ; do
                wp itelic key generate --activations
        done

	pritnf "\nGenerating Renewals...\n"
	wp itelic renewal generate 33

else
	echo "Usage setup.sh <size>"
	exit 0
fi