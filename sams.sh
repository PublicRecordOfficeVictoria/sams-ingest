start() {
	php LDP_PCDM_Action.php 'start'

}

prune() {
	php LDP_PCDM_Action.php 'prune'
}

ACTION=$1 
case ${ACTION} in
start)
    start
    ;;
prune)
    prune
    ;;
*)
    echo "Please either provide 'start', or 'prune' as argument for running the script."
esac
