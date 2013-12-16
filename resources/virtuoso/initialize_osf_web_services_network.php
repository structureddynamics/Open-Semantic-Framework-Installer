<?php

  $db_link = odbc_connect("osf-triples-store", "dba", "dba", SQL_CUR_USE_ODBC);

  $server_address = "";
  
  $appID = "administer";
  
  $rdf =
    "@prefix wsf: <http://purl.org/ontology/wsf#> .
          @prefix void: <http://rdfs.org/ns/void#> .
          @prefix dcterms: <http://purl.org/dc/terms/> .
          @prefix foaf: <http://xmlns.com/foaf/0.1/> .
          @prefix owl: <http://www.w3.org/2002/07/owl#> .
          @prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
          @prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
          @prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
          
          <$server_address/wsf/> rdf:type wsf:WebServiceFramework ;
            wsf:hasWebService <$server_address/wsf/ws/auth/lister/> ;
            wsf:hasWebService <$server_address/wsf/ws/auth/registrar/ws/> ;
            wsf:hasWebService <$server_address/wsf/ws/auth/registrar/access/> ;
            wsf:hasWebService <$server_address/wsf/ws/auth/registrar/group/> ;
            wsf:hasWebService <$server_address/wsf/ws/auth/registrar/user/> ;
            wsf:hasWebService <$server_address/wsf/ws/converter/irjson/> ;
            wsf:hasWebService <$server_address/wsf/ws/converter/common/> ;
            wsf:hasWebService <$server_address/wsf/ws/search/> ;
            wsf:hasWebService <$server_address/wsf/ws/sparql/> ;
            wsf:hasWebService <$server_address/wsf/ws/dataset/create/> ;
            wsf:hasWebService <$server_address/wsf/ws/dataset/read/> ;
            wsf:hasWebService <$server_address/wsf/ws/dataset/update/> ;
            wsf:hasWebService <$server_address/wsf/ws/dataset/delete/> ;
            wsf:hasWebService <$server_address/wsf/ws/crud/create/> ;
            wsf:hasWebService <$server_address/wsf/ws/crud/read/> ;
            wsf:hasWebService <$server_address/wsf/ws/crud/update/> ;
            wsf:hasWebService <$server_address/wsf/ws/crud/delete/> ;
            wsf:hasWebService <$server_address/wsf/ws/revision/delete/> ;
            wsf:hasWebService <$server_address/wsf/ws/revision/diff/> ;
            wsf:hasWebService <$server_address/wsf/ws/revision/lister/> ;
            wsf:hasWebService <$server_address/wsf/ws/revision/read/> ;
            wsf:hasWebService <$server_address/wsf/ws/revision/update/> ;
            wsf:hasWebService <$server_address/wsf/ws/ontology/create/> ;
            wsf:hasWebService <$server_address/wsf/ws/ontology/delete/> ;
            wsf:hasWebService <$server_address/wsf/ws/ontology/read/> ;
            wsf:hasWebService <$server_address/wsf/ws/ontology/update/>.
            
            
          <$server_address/wsf/access/5b2b633495a58612b63724ef71729ea6> rdf:type wsf:Access ;
            dcterms:description \"\"\"This access is used to enable the authentication registrar web service to register new web services to the WSF\"\"\";
            wsf:groupAccess <$server_address/wsf/groups/administrators> ;
            wsf:create \"True\" ;
            wsf:read \"True\" ;
            wsf:update \"True\" ;
            wsf:delete \"True\" ;
            wsf:webServiceAccess <$server_address/wsf/ws/auth/lister/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/auth/registrar/ws/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/auth/registrar/access/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/auth/registrar/group/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/auth/registrar/user/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/dataset/create/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/dataset/read/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/dataset/delete/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/dataset/update/> ;
            wsf:datasetAccess <$server_address/wsf/> .
          
          <$server_address/wsf/access/459f32962858ffa9677a27c4612cb875> rdf:type wsf:Access ;
            dcterms:description \"\"\"This access is used to enable the admin of the WSF to generate and manage ontologies of the WSF\"\"\";
            wsf:groupAccess <$server_address/wsf/groups/administrators> ;
            wsf:create \"True\" ;
            wsf:read \"True\" ;
            wsf:update \"True\" ;
            wsf:delete \"True\" ;
            wsf:webServiceAccess <$server_address/wsf/ws/ontology/create/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/ontology/delete/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/ontology/read/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/ontology/update/> ;
            wsf:datasetAccess <$server_address/wsf/ontologies/> .  
            
          <$server_address/wsf/access/44b0867f6cd9170bead8d774fad4685b> rdf:type wsf:Access ;
            dcterms:description \"\"\"Access to be able to create new datasets\"\"\";
            wsf:groupAccess <$server_address/wsf/groups/administrators> ;
            wsf:create \"True\" ;
            wsf:read \"True\" ;
            wsf:update \"True\" ;
            wsf:delete \"True\" ;
            wsf:webServiceAccess <$server_address/wsf/ws/dataset/create/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/dataset/read/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/dataset/delete/> ;
            wsf:webServiceAccess <$server_address/wsf/ws/dataset/update/> ;
            wsf:datasetAccess <$server_address/wsf/datasets/> .
          
          <$server_address/wsf/ws/auth/registrar/ws/> rdf:type wsf:WebService ;
            dcterms:title \"Web Service(s) Registration web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/auth/registrar/ws/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/auth/registrar/ws/> .
          
          <$server_address/wsf/usage/auth/registrar/ws/> rdf:type wsf:CrudUsage ;
            wsf:create \"True\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"True\" .
          
          <$server_address/wsf/ws/auth/registrar/group/> rdf:type wsf:WebService ;
            dcterms:title \"Web Service(s) Registration web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/auth/registrar/group/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/auth/registrar/group/> .
          
          <$server_address/wsf/usage/auth/registrar/group/> rdf:type wsf:CrudUsage ;
            wsf:create \"True\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"True\" .
          
          <$server_address/wsf/ws/auth/registrar/user/> rdf:type wsf:WebService ;
            dcterms:title \"Web Service(s) Registration web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/auth/registrar/user/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/auth/registrar/user/> .
          
          <$server_address/wsf/usage/auth/registrar/user/> rdf:type wsf:CrudUsage ;
            wsf:create \"True\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"True\" .
              
          <$server_address/wsf/ws/auth/registrar/access/> rdf:type wsf:WebService ;
            dcterms:title \"Access Registration web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/auth/registrar/access/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/auth/registrar/access/> .
          
          <$server_address/wsf/usage/auth/registrar/access/> rdf:type wsf:CrudUsage ;
            wsf:create \"True\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"True\" .
          
          <$server_address/wsf/ws/auth/lister/> rdf:type wsf:WebService ;
            dcterms:title \"Web Service(s), Datasets and Access Listing web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/auth/lister/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/auth/lister/> .
          
          <$server_address/wsf/usage/auth/lister/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .
            
          <$server_address/wsf/ws/crud/create/> rdf:type wsf:WebService ;
            dcterms:title \"Crud Create web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/crud/create/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/crud/create/> .
          
          <$server_address/wsf/usage/crud/create/> rdf:type wsf:CrudUsage ;
            wsf:create \"True\" ;
            wsf:read \"False\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .    

          <$server_address/wsf/ws/crud/read/> rdf:type wsf:WebService ;
            dcterms:title \"Crud Read web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/crud/read/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/crud/read/> .
          
          <$server_address/wsf/usage/crud/read/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .  
            
          <$server_address/wsf/ws/crud/update/> rdf:type wsf:WebService ;
            dcterms:title \"Crud Update web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/crud/update/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/crud/update/> .
          
          <$server_address/wsf/usage/crud/update/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"True\" ;
            wsf:delete \"False\" .    
            
          <$server_address/wsf/ws/crud/delete/> rdf:type wsf:WebService ;
            dcterms:title \"Crud Delete web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/crud/delete/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/crud/delete/> .
          
          <$server_address/wsf/usage/crud/delete/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"False\" ;
            wsf:delete \"True\" .    
          
          <$server_address/wsf/ws/dataset/create/> rdf:type wsf:WebService ;
            dcterms:title \"Dataset Create web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/dataset/create/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/dataset/create/> .
          
          <$server_address/wsf/usage/dataset/create/> rdf:type wsf:CrudUsage ;
            wsf:create \"True\" ;
            wsf:read \"False\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .
          
          <$server_address/wsf/ws/dataset/read/> rdf:type wsf:WebService ;
            dcterms:title \"Dataset Read web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/dataset/read/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/dataset/read/> .
          
          <$server_address/wsf/usage/dataset/read/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .

          <$server_address/wsf/ws/dataset/update/> rdf:type wsf:WebService ;
            dcterms:title \"Dataset Delete web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/dataset/update/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/dataset/update/> .
          
          <$server_address/wsf/usage/dataset/update/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"True\" ;
            wsf:delete \"False\" .  
          
          <$server_address/wsf/ws/dataset/delete/> rdf:type wsf:WebService ;
            dcterms:title \"Dataset Delete web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/dataset/delete/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/dataset/delete/> .
          
          <$server_address/wsf/usage/dataset/delete/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"False\" ;
            wsf:delete \"True\" .  
          
          <$server_address/wsf/ws/ontology/create/> rdf:type wsf:WebService ;
            dcterms:title \"Ontology Create web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/ontology/create/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/ontology/create/> .

          <$server_address/wsf/usage/ontology/create/> rdf:type wsf:CrudUsage ;
            wsf:create \"True\" ;
            wsf:read \"False\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" . 
          
          <$server_address/wsf/ws/ontology/read/> rdf:type wsf:WebService ;
            dcterms:title \"Ontology Read web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/ontology/read/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/ontology/read/> .    
                      
          <$server_address/wsf/usage/ontology/read/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .                         
                      
          <$server_address/wsf/ws/ontology/update/> rdf:type wsf:WebService ;
            dcterms:title \"Ontology Update web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/ontology/update/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/ontology/update/> .
            
          <$server_address/wsf/usage/ontology/update/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"True\" ;
            wsf:delete \"False\" .               
            
          <$server_address/wsf/ws/ontology/delete/> rdf:type wsf:WebService ;
            dcterms:title \"Ontology Delete web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/ontology/delete/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/ontology/delete/> .              

           <$server_address/wsf/usage/ontology/delete/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"False\" ;
            wsf:delete \"True\" . 

          <$server_address/wsf/ws/search/> rdf:type wsf:WebService ;
            dcterms:title \"Search web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/search/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/search/> .
          
          <$server_address/wsf/usage/search/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .  

          <$server_address/wsf/ws/sparql/> rdf:type wsf:WebService ;
            dcterms:title \"SPARQL web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/sparql/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/sparql/> .
          
          <$server_address/wsf/usage/sparql/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .  
            
          <$server_address/wsf/ws/converter/irjson/> rdf:type wsf:WebService ;
            dcterms:title \"Converter irJSON web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/converter/irjson/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/converter/irjson/> .
          
          <$server_address/wsf/usage/converter/irjson/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .
            
          <$server_address/wsf/ws/converter/common/> rdf:type wsf:WebService ;
            dcterms:title \"Converter commON web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/converter/common/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/converter/common/> .
          
          <$server_address/wsf/usage/converter/common/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .
                          
          <$server_address/wsf/ws/revision/delete/> rdf:type wsf:WebService ;
            dcterms:title \"Revisions Delete web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/revision/delete/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/revision/delete/> .
          
          <$server_address/wsf/usage/revision/delete/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"False\" ;
            wsf:delete \"True\" .                                
            
          <$server_address/wsf/ws/revision/diff/> rdf:type wsf:WebService ;
            dcterms:title \"Revisions Diff web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/revision/diff/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/revision/diff/> .
          
          <$server_address/wsf/usage/revision/diff/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .                                
            
          <$server_address/wsf/ws/revision/lister/> rdf:type wsf:WebService ;
            dcterms:title \"Revisions Lister web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/revision/lister/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/revision/lister/> .
          
          <$server_address/wsf/usage/revision/lister/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .                                
            
          <$server_address/wsf/ws/revision/read/> rdf:type wsf:WebService ;
            dcterms:title \"Revisions Read web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/revision/read/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/revision/read/> .
          
          <$server_address/wsf/usage/revision/read/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"True\" ;
            wsf:update \"False\" ;
            wsf:delete \"False\" .                                
            
          <$server_address/wsf/ws/revision/update/> rdf:type wsf:WebService ;
            dcterms:title \"Revisions Update web service\" ;
            wsf:endpoint \"\"\"$server_address/ws/revision/update/\"\"\";
            wsf:hasCrudUsage <$server_address/wsf/usage/revision/update/> .
          
          <$server_address/wsf/usage/revision/update/> rdf:type wsf:CrudUsage ;
            wsf:create \"False\" ;
            wsf:read \"False\" ;
            wsf:update \"True\" ;
            wsf:delete \"False\" .    
            
            
          <$server_address/wsf/groups/administrators> a wsf:Group ;  
            wsf:appID \"$appID\" .
            
          <$server_address/wsf/users/admin> a wsf:User ;  
            wsf:hasGroup <$server_address/wsf/groups/administrators> .
            
          <$server_address/wsf/users/tests-suites> a wsf:User ;  
            wsf:hasGroup <$server_address/wsf/groups/administrators> .                            
          ";
          
  $errors = FALSE;          
          
  $query = "DB.DBA.TTLP_MT('" . preg_replace("/\\\*'/", "\\\'", $rdf). "', '$server_address/wsf/', '$server_address/wsf/')";
          
  if(odbc_exec($db_link, $query) === FALSE)
  {
    $errors = TRUE;
  }

  odbc_close($db_link);

  if($errors)
  {
    echo "errors";
  }
  else
  {
    echo "ok";
  }

?>