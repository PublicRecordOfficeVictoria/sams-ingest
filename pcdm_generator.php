<?php

class pcdm_generator {

   function pcdmObject() {
       return "
			@prefix pcdm: <http://pcdm.org/models#>
		 
			<> a pcdm:Object .
		";
   }

   function ldpDirect($url) {
   	   return "
			@prefix ldp: <http://www.w3.org/ns/ldp#>
			@prefix pcdm: <http://pcdm.org/models#>
 
			<> a ldp:DirectContainer, pcdm:Object ;
  			ldp:membershipResource <" . $url ."> ;
  			ldp:hasMemberRelation pcdm:hasMember .
   	   ";
   }

      function ldpDirectFile($url) {
   	   return "
			@prefix ldp: <http://www.w3.org/ns/ldp#>
			@prefix pcdm: <http://pcdm.org/models#>
 
			<> a ldp:DirectContainer, pcdm:Object ;
  			ldp:membershipResource <" . $url ."> ;
  			ldp:hasMemberRelation pcdm:hasFile .
   	   ";
   }

}

?>