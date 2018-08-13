start() {
	php LDP_PCDM_Action.php 'start'

}

ACTION=$1 
case ${ACTION} in
start)
    start
    ;;
*)
    echo "Please either provide 'start', or 'prune' as argument for running the script."
esac
