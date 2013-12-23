<?php
abstract class Entity
{
    protected $id;    	// entity id
    protected $fields;  // other entity fields

    // initialize object
    public function __construct()
    {
        $this->id = null;
        $this->initFields();
    }
	
	// initializes the non-id fields
	protected abstract function initFields();
  
	// validates the internal values
	// returns TRUE if validation passes, FALSE otherwise
	public abstract function validate();
	
    // saves this entity to the database
    public abstract function save();

    // override magic method to retrieve properties
    public function __get($field)
    {
        if ($field == 'id')
        {
            return $this->id;
        }
        else 
        {
            return $this->fields[$field];
        }
    }

    // override magic method to set properties
    public function __set($field, $value)
    {
        if (array_key_exists($field, $this->fields))
        {
            $this->fields[$field] = $value;
        }
    }
	
	public function __toString()
    {
		$str = '{%s}: ' . get_class($this);
		$str .= 'id=' . $this->id;
		foreach ($this->fields as $key => $value)
		{
			$str .= sprintf('%s=%s', $key, $value);
		}
        return $str;
    }
}
?>
