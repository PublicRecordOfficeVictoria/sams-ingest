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

  function ldpDirectFiles($url) {
    return "
      @prefix ldp: <http://www.w3.org/ns/ldp#>
      @prefix pcdm: <http://pcdm.org/models#>
 
      <> a ldp:DirectContainer, pcdm:Object ;
           ldp:membershipResource <" . $url ."> ;
           ldp:hasMemberRelation pcdm:hasFile .
      ";
  }

  function pcdmFile() {
    return "
      PREFIX pcdm: <http://pcdm.org/models#>
      INSERT {
        <> a pcdm:File
      } WHERE {
      }
      ";
   }

   function cidocDocument($Object_text) {
       return "
            PREFIX crm: <http://www.cidoc-crm.org/cidoc-crm/>
            INSERT {
              <> crm:P70_documents <" . $Object_text . "#> .
            } WHERE {
            }
        ";
   }

  function cidocNote($Object_text) {
    return "
      PREFIX crm: <http://www.cidoc-crm.org/cidoc-crm/>
      INSERT {
        <> crm:P3_has_note \"" . $Object_text . "\" .
      } WHERE {
      }
      ";
  }

  function exifWidthHeight($width,$height) {
    return "
      PREFIX exif: <https://www.w3.org/2003/12/exif/ns#>
      INSERT {   
        <> exif:imageWidth " . $width . " ;
           exif:imageHeight " . $height . " .
      }
      WHERE { }
      ";
  }

}

?>
