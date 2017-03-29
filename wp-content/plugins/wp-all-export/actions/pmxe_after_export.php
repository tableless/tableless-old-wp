<?php

function pmxe_pmxe_after_export($export_id, $export)
{		
	if ( ! empty(PMXE_Plugin::$session) and PMXE_Plugin::$session->has_session() )
	{
		PMXE_Plugin::$session->set('file', '');
		PMXE_Plugin::$session->save_data();				
	}

	if ( ! $export->isEmpty())
	{						
		$splitSize = $export->options['split_large_exports_count'];			

		$exportOptions = $export->options;
		// remove previously genereted chunks
		if ( ! empty($exportOptions['split_files_list']) and ! $export->options['creata_a_new_export_file'] )
		{
			foreach ($exportOptions['split_files_list'] as $file) {
				@unlink($file);
			}
		}		

		$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

		if ( ! $is_secure_import)
		{
			$filepath = get_attached_file($export->attch_id);					
		}
		else
		{
			$filepath = wp_all_export_get_absolute_path($export->options['filepath']);
		}	

		$is_export_csv_headers = apply_filters('wp_all_export_is_csv_headers_enabled', true, $export->id);

        if ( isset($export->options['include_header_row'])) {
            $is_export_csv_headers = $export->options['include_header_row'];
        }

        // Remove headers row from CSV file
        if ( empty($is_export_csv_headers) && @file_exists($filepath) && $export->options['export_to'] == 'csv' && $export->options['export_to_sheet'] == 'csv' ){

            $tmp_file = str_replace(basename($filepath), 'iteration_' . basename($filepath), $filepath);
            copy($filepath, $tmp_file);
            $in  = fopen($tmp_file, 'r');
            $out = fopen($filepath, 'w');

            $headers = fgetcsv($in, 0, XmlExportEngine::$exportOptions['delimiter']);

            if (is_resource($in)) {
                $lineNumber = 0;
                while ( ! feof($in) ) {
                    $data = fgetcsv($in, 0, XmlExportEngine::$exportOptions['delimiter']);
                    if ( empty($data) ) continue;
                    $data_assoc = array_combine($headers, array_values($data));
                    $line = array();
                    foreach ($headers as $header) {
                        $line[$header] = ( isset($data_assoc[$header]) ) ? $data_assoc[$header] : '';
                    }
                    if ( ! $lineNumber && XmlExportEngine::$exportOptions['include_bom']){
                        fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
                        fputcsv($out, $line, XmlExportEngine::$exportOptions['delimiter']);
                    }
                    else{
                        fputcsv($out, $line, XmlExportEngine::$exportOptions['delimiter']);
                    }
                    apply_filters('wp_all_export_after_csv_line', $out, XmlExportEngine::$exportID);
                    $lineNumber++;
                }
                fclose($in);
            }
            fclose($out);
            @unlink($tmp_file);
        }	

		// Split large exports into chunks
		if ( $export->options['split_large_exports'] and $splitSize < $export->exported )
		{

			$exportOptions['split_files_list'] = array();							

			if ( @file_exists($filepath) )
			{					

				switch ($export->options['export_to']) 
				{
					case 'xml':

						$main_xml_tag = apply_filters('wp_all_export_main_xml_tag', $export->options['main_xml_tag'], $export->id);
						$record_xml_tag = apply_filters('wp_all_export_record_xml_tag', $export->options['record_xml_tag'], $export->id);

						$records_count = 0;
						$chunk_records_count = 0;
						$fileCount = 1;

						$feed = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"  . "\n" . "<".$main_xml_tag.">";

						$file = new PMXE_Chunk($filepath, array('element' => $record_xml_tag, 'encoding' => 'UTF-8'));
						// loop through the file until all lines are read				    				    			   			   	    			    			    
					    while ($xml = $file->read()) {				    	

					    	if ( ! empty($xml) )
					      	{					      		
					      		$chunk = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"  . "\n" . $xml;					      		
						      					      		
						      	$dom = new DOMDocument('1.0', "UTF-8");
								$old = libxml_use_internal_errors(true);
								$dom->loadXML($chunk); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load											
								libxml_use_internal_errors($old);
								$xpath = new DOMXPath($dom);								

								$records_count++;
								$chunk_records_count++;
								$feed .= $xml;								
							}

							if ( $chunk_records_count == $splitSize or $records_count == $export->exported ){
								$feed .= "</".$main_xml_tag.">";
								$outputFile = str_replace(basename($filepath), str_replace('.xml', '', basename($filepath)) . '-' . $fileCount++ . '.xml', $filepath);
								file_put_contents($outputFile, $feed);
								if ( ! in_array($outputFile, $exportOptions['split_files_list']))
						        	$exportOptions['split_files_list'][] = $outputFile;
								$chunk_records_count = 0;
								$feed = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"  . "\n" . "<".$main_xml_tag.">";
							}

						}							
						break;
					case 'csv':
						$in = fopen($filepath, 'r');

						$rowCount  = 0;
						$fileCount = 1;
						$headers = fgetcsv($in);
						while (!feof($in)) {
						    $data = fgetcsv($in);
						    if (empty($data)) continue;
						    if (($rowCount % $splitSize) == 0) {
						        if ($rowCount > 0) {
						            fclose($out);
						        }						        
						        $outputFile = str_replace(basename($filepath), str_replace('.csv', '', basename($filepath)) . '-' . $fileCount++ . '.csv', $filepath);
						        if ( ! in_array($outputFile, $exportOptions['split_files_list']))
						        	$exportOptions['split_files_list'][] = $outputFile;

						        $out = fopen($outputFile, 'w');						        
						    }						    
						    if ($data){				
						    	if (($rowCount % $splitSize) == 0) {
						    		fputcsv($out, $headers);
						    	}		    	
						        fputcsv($out, $data);
						    }
						    $rowCount++;
						}
						fclose($in);	
						fclose($out);							

						break;
					
					default:
						
						break;
				}				

				$export->set(array('options' => $exportOptions))->save();
			}	
		}			

		// make a temporary copy of current file
		if ( empty($export->parent_id) and @file_exists($filepath) and @copy($filepath, str_replace(basename($filepath), '', $filepath) . 'current-' . basename($filepath)))
		{
			$exportOptions = $export->options;
			$exportOptions['current_filepath'] = str_replace(basename($filepath), '', $filepath) . 'current-' . basename($filepath);						
			$export->set(array('options' => $exportOptions))->save();
		}

		// genereta export bundle
		$export->generate_bundle();		

		if ( ! empty($export->parent_id) ) 
		{
			$parent_export = new PMXE_Export_Record();
			$parent_export->getById($export->parent_id);
			if ( ! $parent_export->isEmpty() )
			{				
				$parent_export->generate_bundle(true);						
			}
		} 

		// clean session 
		if ( ! empty(PMXE_Plugin::$session) and PMXE_Plugin::$session->has_session() )
		{
			PMXE_Plugin::$session->clean_session( $export->id );				
		}
	}	
}