<?php

namespace Elegant\Foundation\Http;

class FormRequest
{
    /**
     * Form Validation variable
     *
     * @var CI_Form_validation
     */
    protected $form_validation;

    /**
     * Input variable
     *
     * @var CI_Input
     */
    protected $input;

    /**
     * Validated data
     *
     * @var array
     */
    protected $valid_data = [];

    /**
     * Error message data
     *
     * @var array
     */
    protected $error_data = [];

    /**
     * All submitted data
     *
     * @var array
     */
    protected $all_data = [];

    /**
     * All attributes data
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * All attributes data
     *
     * @var array
     */
    protected $messages = [];


    public function __construct(CI_Form_validation $form_validation = null, CI_Input $input = null)
    {
        if (!isset($form_validation)) {
            ci()->load->library('form_validation');
            $input = ci()->input;
            $form_validation = ci()->form_validation;
        }

        $form_validation->set_error_delimiters('<span class="invalid-feedback" role="alert"><strong>', '</strong></span>');

        $this->input = $input;
        $this->form_validation = $form_validation;
    }

    /**
     * Return rules validation
     *
     * @return array
     */
    public function rules() : array
    {
        return [];
    }

    /**
     * Return messages by each rule
     *
     * @return array
     */
    public function messages() : array
    {
        return $this->messages;
    }

    /**
     * Return different attribute name
     *
     * @return array
     */
    public function attributes() : array
    {
        return $this->attributes;
    }

    /**
     * Validated method check if exist abstract methods
     * or return the default validation message and attributes
     * of codeigniter framework
     *
     * @return bool
     */
    public function valid() : bool
    {
        foreach($this->rules() as $index => $rules) {
            $string_rules = $this->rulesToString($rules);

            $attributes = '';

            if(method_exists($this, 'attributes') && !empty($this->attributes())) {
                $attributes = isset($this->attributes()[$index]) ? mb_strtolower($this->attributes()[$index], 'UTF-8') : '';
            }

            $this->form_validation->set_rules($index, $attributes, $string_rules);

            if(method_exists($this, 'messages') && !empty($this->messages())) {
                foreach($this->rules() as $filed_name => $_rules) {
                    foreach ($_rules as $rule) {
                        $rule_clear = $rule;

                        if (strpos($rule, '[') !== false) {
                            $rule_clear = substr($rule, 0, strpos($rule, "["));
                        }

                        $message_index = $filed_name . '.' . $rule_clear;

                        if (isset($this->messages()[$message_index])) {
                            if (strpos($rule_clear, 'callback_') === 0) {
                                $rule_clear = substr($rule_clear, 9);
                            }

                            if(Str::contains($this->messages()[$message_index], ':attribute')) {
                                $string_message = str_replace(
                                    [':attribute'],
                                    [$this->attributes()[$filed_name], strtoupper($this->attributes()[$filed_name]), ucfirst($this->attributes()[$filed_name])],
                                    $this->messages()[$message_index]
                                );
                            } else {
                                $string_message = $this->messages()[$message_index];
                            }

                            $this->form_validation->set_rules($filed_name, $this->attributes()[$filed_name], $this->rulesToString($this->rules()[$filed_name]), [$rule_clear => $string_message]);
                            $this->form_validation->set_message($rule_clear, $string_message);
                        }
                    }
                }
            }
        }

        if($this->form_validation->run()) {
            foreach ($this->input->post() as $key => $input) {
                $check_key = is_array($input) ? $key . '[]' : $key;
                if (array_key_exists($check_key, $this->rules())) {
                    $this->valid_data[$key] = $this->input->post($key);
//                    if ($key == 'description') {
//                        $this->valid_data[$key] = $this->input->post($key);
//                    } else {
//                        $this->valid_data[$key] = $this->input->post($key, true);
//                    }
                }
                $this->all_data[$key] = $this->input->post($key);
//                if ($key == 'description') {
//                    $this->all_data[$key] = $this->input->post($key);
//                } else {
//                    $this->all_data[$key] = $this->input->post($key, true);
//                }
            }
            if (isset($_FILES)) {
                foreach ($_FILES as $key => $input) {
                    if (array_key_exists($key, $this->rules())) {
                        $this->valid_data[$key] = $input;
                    }
                    $this->all_data[$key] = $input;
                }
            }
        }

        $this->error_data = $this->form_validation->error_array();

        return $this->form_validation->run();
    }

    /**
     * @param array $attributes
     * @return FormRequest
     */
    public function setAttributeNames(array $attributes): FormRequest
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param array $messages
     * @return FormRequest
     */
    public function setMessages(array $messages): FormRequest
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Return post data as array
     *
     * @return array
     */
    public function all() : array
    {
        return $this->all_data;
    }

    public function errors(): array
    {
        return $this->error_data;
    }

    /**
     * Return valid post data as object
     *
     * @return array
     */
    public function validated() : array
    {
        return $this->valid_data;
    }

    /**
     * Convert array data validation rules to string
     * rules for accept codeigniter rule
     *
     * @param array $data
     * @return string
     */
    private function rulesToString(array $data): string
    {
        $string_rules = '';

        foreach ($data as $rule) {
            $string_rules != "" && $string_rules .= "|";
            $string_rules .= $rule;
        }

        return $string_rules;
    }
}