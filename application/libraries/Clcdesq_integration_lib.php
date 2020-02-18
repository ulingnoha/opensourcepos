<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CLCdesq API REST client Connector
 *
 * Interface for communicating with the CLCdesq Product Push API
 */

class Clcdesq_integration_lib
{
	private $CI;
	private $api_key;
	private $api_url;
	
	/**
	 * Constructor
	 */
	public function __construct($api_key = '')
	{
		$this->CI =& get_instance();
		
		$this->api_key	= $this->CI->encryption->decrypt($this->CI->Appconfig->get('clcdesq_api_key'));
		$this->api_url	= $this->CI->encryption->decrypt($this->CI->Appconfig->get('clcdesq_api_url'));
	}
	
	public function new_product_push(array $data)
	{
		if(!$this->is_enabled())
		{
			return NULL;
		}
		
		$pushdata	= $this->populate_api_data($data);
		
		$json = json_encode($pushdata);
		
		$clcdesq_guid = $this->send_data($this->api_url, $this->api_key, $json);
		
		log_message("ERROR", "API Results: $clcdesq_guid");
		//TODO: The result of the API Product Push should be a GUID.  Store that in the database as an attribute for the pushed product
		return NULL;
	}
	
	/**
	 * Send API request to update the item. Since CLCdesq does not have a partial update function, it sends the item with all the same information as before, but also including the GUID.
	 *
	 * @param	array	$data	Partial data needed to
	 * @return 	boolean			TRUE is returned if the push was successful or FALSE if there was some error.
	 */
	public function update_product_push(array $data)
	{
		if(!$this->is_enabled())
		{
			return NULL;
		}
		
		$pushdata	= $this->populate_api_data($data);
		
		$json = json_encode($pushdata);
		
		//		$clcdesq_guid = $this->send_data($this->api_url, $this->api_key, $json);
		
		//TODO: For now, the update product push is identical to the new product push except that we are sending the GUID
		return NULL;
	}
	
	/**
	 * Send API request to delete the item. Since CLCdesq does not have a true delete function, it sends the item with Published and ShowOnWebsite set to FALSE.
	 *
	 * @param	array	$data
	 * @return 	boolean			TRUE is returned if the push was successful or FALSE if there was some error.
	 */
	public function delete_product_push(array $data)
	{
		if(!$this->is_enabled())
		{
			return NULL;
		}
		
		$pushdata	= $this->populate_api_data($data);
		
		//Delete specific flags
		$pushdata['Published'] 		= FALSE;
		$pushdata['ShowOnWebsite']	= FALSE;
		
		$json = json_encode($pushdata);
		//		$clcdesq_guid = $this->send_data($this->api_url, $this->api_key, $json);
		
		//TODO: Figure out exactly what the results that it sends back are and return a failure on error.
		return NULL;
	}
	
	/**
	 * Checks to see if the CLCdesq Integration is enabled
	 *
	 * @return	boolean	TRUE if enabled or FALSE if disabled.
	 */
	private function is_enabled()
	{
		if($this->CI->Appconfig->get('clcdesq_enable') != 1)
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Sends the POST JSON request via cURL
	 *
	 * @param	string	$url	The API URL to call.
	 * @param	string	$key	The API key to use in the request.
	 * @param 	string	$json	The JSON formatted data to send.
	 * @return	string			Returns the resulting error or GUID from the API
	 */
	private function send_data(string $url, string $key, string $json)
	{
		$url = 'https://clcdesq.free.beeceptor.com';
		$curl_resource	= curl_init($url);
		curl_setopt($curl_resource, CURLOPT_HTTPHEADER, array('Content-type: application/json',"APIKEY: $key"));
		curl_setopt($curl_resource, CURLOPT_POST, TRUE);
		curl_setopt($curl_resource, CURLOPT_POSTFIELDS, $json);
		curl_setopt($curl_resource, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl_resource, CURLOPT_SSL_VERIFYPEER, FALSE);
		
		$result = curl_exec($curl_resource);
		curl_close($curl_resource);
		return $result;
	}
	
	//TODO: This probably belongs in a model?
	/**
	 * Populates the API data needed for the push.  This is used by all three product_push member functions.
	 *
	 * @param 	array	data	Complete data needed to build the Array.
	 * @return	array			Array to be used in the product push.
	 */
	private function populate_api_data($data)
	{
		//TODO: Figure out how to have Items pass the item_id for an item update
		$item_id		= $data['item_id'];
		$config_data	= array();
		
		foreach($this->CI->Appconfig->get_all()->result() as $app_config)
		{
			$config_data[$app_config->key] = $app_config->value;
		}
		
		//TODO: EVERYTHING NEEDS TO BE IN A PRODUCTAO BRACKETS
		$api_data = array('ProductAO' => array(
			'AspectRatio' 			=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_aspectratio'])->attribute_value,
			'AudienceRating' 		=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_audiencerating'])->attribute_value,
			'AudioFormat' 			=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_audioformat'])->attribute_value,
			'AudioTrackListing' 	=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_audiotracklisting'])->attribute_value,
			'AuthorsText' 			=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_authorstext'])->attribute_value,
			'Barcode' 				=> $data['item_number'],
			'Binding' 				=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_binding'])->attribute_value,
			'BookForeword' 			=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_bookforeword'])->attribute_value,
			'BookIndex' 			=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_bookindex'])->attribute_value,
			'BookSampleChapter' 	=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_booksamplechapter'])->attribute_value,
			'Contributors' 			=> array('Contributors' => $this->get_contributor_ao_array($item_id)),
			'DateAdded'	 			=> $this->get_date_added($item_id),
			'Depth'		 			=> (float)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_depth'])->attribute_decimal,
			'Description' 			=> $data['description'],
			'DimensionUnit' 		=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_depth'])->attribute_decimal !== NULL ? $this->CI->Attribute->get_info((int)$config_data['clcdesq_depth'])->definition_unit : NULL,
			'DiscountGroup' 		=> $this->get_product_discount_group_ao_array($item_id),
			'EAN' 					=> $this->get_ean($this->get_isbn($data['item_number'])),
			'Format' 				=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_format'])->attribute_value,
			'Height'		 		=> (float)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_height'])->attribute_decimal,
			'InternalCode' 			=> $item_id,
			'ISBN'		 			=> $this->get_isbn($data['item_number']),
			'KindId'		 		=> $data['category'] == 'Books' ? 1 : NULL,		/* Regular Book*/
			'Language'	 			=> $this->get_language_ao_array((int)$item_id),
			'MediaType'	 			=> $this->get_media_type_ao_array($data['category']),
			'NumberOfDiscs' 		=> (int)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_numberofdiscs'])->attribute_decimal,
			'NumberOfPages' 		=> (int)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_numberofpages'])->attribute_decimal,
			'OriginalTitle' 		=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_originaltitle'])->attribute_value,
			'Price' 				=> $data['unit_price'],
			'PriceWithoutVAT'		=> $this->get_price_without_VAT($data['unit_price']),
			'PriceNote'				=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_pricenote'])->attribute_value,
			'Producer'				=> $this->get_producer_user_ao_array($item_id),
			'ProductStatusProducer' => $this->get_product_status_producer_ao_array($item_id),
			'PriceCurrency'			=> $config_data['currency_code'] !== '' ? $config_data['currency_code'] : NULL,
			'Published' 			=> $data['deleted'] == FALSE ? TRUE : FALSE,
			'PublisherRRP'			=> (float)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_publisherrrp'])->attribute_decimal,
			'ReducedPrice'			=> (float)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_reducedprice'])->attribute_decimal,
			'ReducedPriceStartDate'	=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_reducedpricestartdate'])->attribute_date,
			'ReducedPriceEndDate'	=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_reducedpriceenddate'])->attribute_date,
			'ReleaseDate' 			=> date('Y-m-d\TH:i:s',strtotime($this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_releasedate'])->attribute_date)),
			'RunningTime'			=> (int)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_runningtime'])->attribute_decimal,
			'Series'				=> $this->get_product_series_ao_array($item_id),
			'StockCount'			=> $this->get_total_quantity($item_id),
			'StockOnOrder'			=> (int)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_stockonorder'])->attribute_decimal,
			'Supplier'				=> $this->get_supplier_user_ao_array($data['supplier_id']),
			'ShowOnWebsite'			=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_showonwebsite'])->attribute_value,
			'Subtitle'				=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_subtitle'])->attribute_value,
			'Subtitles'				=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_subtitles'])->attribute_value,
			'TeaserDescription'		=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_teaserdescription'])->attribute_value,
			'Title' 				=> $data['name'],
			'UniqueId'				=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_uniqueid'])->attribute_value,
			'UPC' 					=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_upc'])->attribute_value,
			'VatPercent'			=> (float)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_vatpercent'])->attribute_decimal,
			'VideoTrailerEmbedCode'	=> $data['videotrailerembedcode'],
			'Weight'				=> (float)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_weight'])->attribute_decimal,
			'WeightForShipping'		=> (float)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_weightforshipping'])->attribute_decimal,
			'WeightUnit'			=> $this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_weight'])->attribute_decimal !== NULL ? $this->CI->Attribute->get_info((int)$config_data['clcdesq_weight'])->definition_unit : NULL,
			'Width'					=> (float)$this->CI->Attribute->get_attribute_value($item_id, (int)$config_data['clcdesq_width'])->attribute_decimal,
			'Categories' 			=> array('CategoryAO' => $this->get_category_ao_array($item_id, 'Products', 0))
		));
		
		$api_data = $this->array_filter_recursive($api_data);
		
		ob_start();
		var_dump($api_data);
		$result = ob_get_clean();
		log_message('Error',$result);
		
		return $api_data;
	}
	
	/**
	 *
	 * @param unknown $item_id
	 * @return NULL[]|string[]|boolean[]
	 */
	private function get_contributor_ao_array($item_id)
	{
		$contributor 		= $this->CI->Attribute->get_attribute_value($item_id, (int)$this->CI->Appconfig->get('clcdesq_authorstext'))->attribute_value;
		
		if($contributor != NULL)
		{
			$author = $this->parse_author($contributor);
		}
		
		if($author == NULL)
		{
			return NULL;
		}
		else
		{
			$contributor_ao	= array('Contributor' => array(
				'Id'			=> null,
				'Guid'			=> null,
				'FirstName'		=> $author['first_name'],
				'LastName'		=> $author['last_name'],
				'DisplayName'	=> $author['display_name'],
				'Description'	=> null,
				'Role'			=> 'A01',	//Only authors are submitted at this time
				'Published'		=> TRUE
			));
			
			return $contributor_ao;
		}
	}
	
	/**
	 * Parses out the First Name, Last Name and Display Name of a Given input
	 *
	 * @param	string	$input	Text to parse for author details
	 * @return	array			An array containing First Name, Last Name and Display Name Strings
	 */
	private function parse_author(string $input)
	{
		//Not Last, First or First Last format
		if(strpos($input,',') === FALSE && strpos($input,' ') === FALSE)
		{
			$author 	= array('display_name' => trim($input));
		}
		//Last, First format
		else if(strpos($input,',') !== FALSE)
		{
			$author		= array(
				'last_name'		=> trim(strtok($input,',')),
				'first_name'	=> trim(substr($input, strpos($input, ',') + 1)),
				'display_name'	=> trim($input)
			);
		}
		//First Last format
		else
		{
			$author		= array(
				'last_name'		=> trim(substr($input, strrpos($input, ' ') + 1)),
				'first_name'	=> trim(substr($input, 0, strrpos($input, ' ')))
			);
			
			$author +=	['display_name'	=> trim($author['last_name'] . ', ' .$author['first_name'])];
		}
		
		return $author;
	}
	
	/**
	 * Retrieve the date that the item was first added.
	 *
	 * @param	int		$item_id	The ID of the item to retrieve date added.
	 * @return	string				The Date this item was first added.
	 */
	private function get_date_added(int $item_id)
	{
		$date_added = $this->CI->Inventory->get_inventory_data_for_item($item_id)->result_array();
		
		return date('Y-m-d\TH:i:s',strtotime($date_added[0]['trans_date']));
	}
	
	/**
	 * Generate EAN code from ISBN
	 *
	 * @param	string|NULL	$isbn		The ISBN-13 or ISBN-10 of the item
	 * @return 	string|NULL				The EAN code of the item or NULL if there is no ISBN
	 */
	private function get_ean($isbn)
	{
		if($isbn !== NULL)
		{
			return preg_replace('/[^0-9]/', '', $isbn);
		}
		
		return NULL;
	}
	
	/**
	 * Generate ISBN from Barcode if the Barcode is properly formatted
	 *
	 * @param	string		$barcode	The barcode of the item.
	 * @return 	NULL|string				Returns the ISBN-10, ISBN-13 or NULL if no ISBN is in the barcode.
	 */
	private function get_isbn(string $barcode)
	{
		$isbn_candidate = preg_replace("/[^0-9a-zA-Z]/", "", $barcode);
		
		if(strlen($isbn_candidate) != 10 && strlen($isbn_candidate) !== 13)
		{
			return NULL;
		}
		else
		{
			return $isbn_candidate;
		}
	}
	
	/**
	 * Prepares a LanguageAO array to be sent in the API.
	 *
	 * @param	int		$item_id	The unique identifier for which to get
	 * @return	array				An associative array containing the LanguageAO information.
	 */
	private function get_language_ao_array(int $item_id)
	{
		$language_shortname = $this->CI->Attribute->get_attribute_value($item_id, (int)$this->CI->Appconfig->get('clcdesq_language'))->attribute_value;
		
		$language_ao = array(
			'ShortName'			=> $language_shortname,
			'OnixLanguageCode'	=> NULL
		);
		
		return $language_ao;
	}
	
	/**
	 * Prepares a MediaTypeAO array to be sent in the API.
	 *
	 * @param	string	$category	The category translates specifically to the MediaTypeAO Title.
	 * @return	array				An associative array containing the MediaTypeAO information
	 */
	private function get_media_type_ao_array(string $category)
	{
		$mediatype_ao	= array(
			'Id'				=> NULL,
			'Title'				=> $category,
			'Description'		=> NULL,
			'DefaultWeight'		=> NULL,
			'Published'			=> TRUE,
			'ShortName'			=> NULL,
			'DefaultVatPercent'	=> NULL
		);
		
		return $mediatype_ao;
	}
	
	/**
	 * Given the price of the item determines the price without VAT included.
	 *
	 * @param	float		$price	Price of the item.
	 * @return 	float|NULL			Returns the price of the item without VAT included. If VAT is not included in the price, then it returns the given price.
	 */
	private function get_price_without_vat(float $price)
	{
		$tax_rate		= (float)$this->CI->Appconfig->get('default_tax_1_rate');
		$tax_included	= (bool)$this->CI->Appconfig->get('tax_included');
		
		if($tax_rate != NULL && $tax_included)
		{
			$tax_percent = $tax_rate/100;
			return 	$price - ceil(($price * $tax_percent)*100)/100;
		}
		else if($tax_rate != NULL)
		{
			return $price;
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Prepares a ProducerUserAO array to be sent in the API.
	 *
	 * @param	int		$item_id	The unique identifier of the item to generate the ProducerUserAO for.
	 * @return	array				An associative array containing the ProducerUserAO information
	 */
	private function get_producer_user_ao_array(int $item_id)
	{
		$producer_user_ao	= array(
			'UniqueId'				=> NULL,
			'FirstName'				=> NULL,
			'LastName'				=> NULL,
			'Email'					=> NULL,
			'DateAdded'				=> NULL,
			'AllowPaymentOnAccount'	=> NULL,
			'ActivePublic'			=> FALSE,
			'ActiveAdmin'			=> FALSE,
			'IsSupplier'			=> FALSE,
			'IsProducer'			=> TRUE,
			'PasswordHash'			=> NULL,
			'PasswordSalt'			=> NULL,
			'Username'				=> NULL,
			'CompanyName'			=> $this->CI->Attribute->get_attribute_value($item_id, (int)$this->CI->Appconfig->get('clcdesq_producer'))->attribute_value,
			'CompanyRegistration'	=> NULL,
			'CompanyVatCode'		=> NULL,
			'DiscountGroup'			=> NULL
		);
		
		return $producer_user_ao;
	}
	
	/**
	 * Prepares a ProductStatusProducerAO array to be sent in the API.
	 *
	 * @param	int		$item_id	The unique identifier of the item to generate the ProductStatusProducerAO for.
	 * @return	array				An associative array containing the ProductStatusProducerAO information.
	 */
	private function get_product_status_producer_ao_array(int $item_id)
	{
		$product_status_producer_ao	= array(
			'Id'							=> NULL,
			'Name'							=> "Available or Order",
			'DisplayStatus'					=> TRUE,
			'DisplayProduct'				=> TRUE,
			'AllowOrdering'					=> TRUE,
			'AllowOrderingMinStockAmount'	=> 0,
			'EnforceActualStock'			=> TRUE,
			'DisplayStock'					=> TRUE,
			'StatusExplanation'				=> NULL,
			'StatusColorHex'				=> NULL
		);
		
		return $product_status_producer_ao;
	}
	
	/**
	 * Prepares a ProductSeriesAO array to be sent in the API
	 *
	 * @param	int		$item_id	The unique identifier of the item to generate the ProductSeriesAO for.
	 * @return	array				An associative array containing the ProductSeriesAO information.
	 */
	private function get_product_series_ao_array(int $item_id)
	{
		$product_series_ao	= array(
			'Id'			=> NULL,
			'UID'			=> NULL,
			'Title'			=> $this->CI->Attribute->get_attribute_value($item_id, (int)$this->CI->Appconfig->get('clcdesq_series'))->attribute_value,
			'Description'	=> NULL,
			'DateAdded'		=> $this->get_date_added($item_id),
			'Published'		=> TRUE
		);
		
		return $product_series_ao;
	}
	
	//TODO: We may want to move this to the Item_quantity model
	/**
	 * Returns the total quantity available from all suppliers.
	 *
	 * @param	int	$item_id	The unique identifier of the item to get the total quantity for.
	 * @return	int				The total quantity between all stock locations.
	 */
	private function get_total_quantity($item_id)
	{
		$total_quantity		= 0;
		$stock_locations	= $this->CI->Stock_location->get_all()->result_array();
		
		foreach($stock_locations as $location => $location_data)
		{
			$location_id = $location_data['location_id'];
			$location_name = $location_data['location_name'];
			
			$total_quantity += $this->CI->Item_quantity->get_item_quantity($item_id, $location_id)->quantity;
		}
		
		return $total_quantity;
	}
	
	/**
	 * Prepares a SupplierUserAO array to be sent in the API
	 *
	 * @param	int|NULL		$item_id	The unique identifier of the item to generate the SupplierUserAO for.
	 * @return	array				An associative array containing the SupplierUserAO information.
	 */
	private function get_supplier_user_ao_array($supplier_id)
	{
		$supplier_info = $this->CI->Supplier->get_info($supplier_id);
		
		$supplier_user_ao	= array(
			'UniqueId'				=> NULL,
			'FirstName'				=> $supplier_info->first_name,
			'LastName'				=> $supplier_info->last_name,
			'Email'					=> $supplier_info->email,
			'DateAdded'				=> NULL,
			'AllowPaymentOnAccount'	=> NULL,
			'ActivePublic'			=> FALSE,
			'ActiveAdmin'			=> FALSE,
			'IsSupplier'			=> TRUE,
			'IsProducer'			=> FALSE,
			'PasswordHash'			=> NULL,
			'PasswordSalt'			=> NULL,
			'Username'				=> NULL,
			'CompanyName'			=> $supplier_info->company_name,
			'CompanyRegistration'	=> NULL,
			'CompanyVatCode'		=> $supplier_info->tax_id,
			'DiscountGroup'			=> NULL
		);
		
		return $supplier_user_ao;
	}
	
	/**
	 * Prepares a CategoryAO array to be sent in the API
	 *
	 * @param	int		$item_id	The unique identifier of the item to generate the ProductSeriesAO for.
	 * @return	array				An associative array containing the ProductSeriesAO information.
	 */
	private function get_category_ao_array($item_id, string $title = NULL, int $level)
	{
		if($title == NULL)
		{
			return NULL;
		}
		else
		{
			//Products->$data['category']->$attribute['location']->$attribute->['category']
			switch($level)
			{
				case 0:	//Products
					$next_title = $this->CI->Item->get_info($item_id)->category;
					break;
					
				case 1: //Category (Books, Media, Gifts, etc.)
					$next_title = $this->CI->Attribute->get_attribute_value($item_id, (int)$this->CI->Appconfig->get('clcdesq_location'))->attribute_value;
					break;
					
				case 2: //Location Attribute(Gift and Travel, Reference, Azerbaijani, etc.)
					$next_title = $this->CI->Attribute->get_attribute_value($item_id, (int)$this->CI->Appconfig->get('clcdesq_category'))->attribute_value;
					break;
					
				default:
					$next_title = NULL;
					break;
			}
			
			$category_ao	= array(
				'Id'	=> NULL,
				'Title'	=> $title,
				'children' => array('CategoryAO' => $this->get_category_ao_array($item_id, $next_title, $level+1))
			);
		}
		
		return $category_ao;
	}
	
	/**
	 * Prepares the ProductDiscountGroup API Object for inclusion in the API data
	 *
	 * @param	int	$item_id	Item ID for which to
	 */
	private function get_product_discount_group_ao_array($item_id)
	{
		$product_discount_group_ao	= array('DiscountGroup' => array(
			'UID'	=> NULL,
			'Name'	=> NULL,
			'Description' => NULL
		));
		
		return $product_discount_group_ao;
	}
	
	/**
	 * Recursively filters out FALSE values (NULL, '' and 0) from Array
	 *
	 * @param	array|string	$input	The array or array value to analize
	 * @return	array|string			The resulting array element or array
	 */
	private function array_filter_recursive($input)
	{
		foreach ($input as &$value)
		{
			if (is_array($value))
			{
				$value = $this->array_filter_recursive($value);
			}
		}
		
		return array_filter($input);
	}
}