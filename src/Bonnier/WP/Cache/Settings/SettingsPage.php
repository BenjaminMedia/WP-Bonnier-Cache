<?php

namespace Bonnier\WP\Cache\Settings;

class SettingsPage
{
    const SETTINGS_KEY = 'wp_cache_settings';
    const SETTINGS_GROUP = 'wp_cache_settings_group';
    const SETTINGS_SECTION = 'wp_cache_settings_section';
    const SETTINGS_PAGE = 'wp_cache_settings_page';
    const Settings_PAGE_NAME = 'Bonnier Cache';
    const Settings_PAGE_TITLE = 'WP Bonnier Cache settings:';
    const NOTICE_PREFIX = 'WP Bonnier Cache:';
    const ROLES_PREFIX = 'bp_wa_';

    private $settingsFields = [
        'host_url' => [
            'type' => 'text',
            'name' => 'Cache Host URL',
        ],
    ];

    private $settingsValues;

    public function __construct()
    {
        $this->settingsValues = get_option(self::SETTINGS_KEY);
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function get_cache_host()
    {
        return $this->get_setting_value('host_url') ?: '';
    }

    public function print_error($error)
    {
        $out = "<div class='error settings-error notice is-dismissible'>";
        $out .= '<strong>'.self::NOTICE_PREFIX."</strong><p>$error</p>";
        $out .= '</div>';
        echo $out;
    }

    /**
     * Add options page.
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            self::Settings_PAGE_NAME,
            'manage_options',
            self::SETTINGS_PAGE,
            [$this, 'create_admin_page']
        );
    }

    /**
     * Options page callback.
     */
    public function create_admin_page()
    {
        // Set class property?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields(self::SETTINGS_GROUP);
        do_settings_sections(self::SETTINGS_PAGE);
        submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings.
     */
    public function register_settings()
    {
        register_setting(
            self::SETTINGS_GROUP, // Option group
            self::SETTINGS_KEY, // Option name
            [$this, 'sanitize'] // Sanitize
        );

        add_settings_section(
            self::SETTINGS_SECTION, // ID
            self::Settings_PAGE_TITLE, // Title
            [$this, 'print_section_info'], // Callback
            self::SETTINGS_PAGE // Page
        );

        foreach ($this->settingsFields as $settingsKey => $settingField) {
            add_settings_field(
                $settingsKey, // ID
                $settingField['name'], // Title
                [$this, $settingsKey], // Callback
                self::SETTINGS_PAGE, // Page
                self::SETTINGS_SECTION // Section
            );
        }
    }

    /**
     * Sanitize each setting field as needed.
     *
     * @param array $input Contains all settings fields as array keys
     *
     * @return array
     */
    public function sanitize($input)
    {
        $sanitizedInput = [];

        foreach ($this->settingsFields as $fieldKey => $settingsField) {
            if (isset($input[$fieldKey])) {
                if ($settingsField['type'] === 'checkbox') {
                    $sanitizedInput[$fieldKey] = absint($input[$fieldKey]);
                }
                if ($settingsField['type'] === 'text' || $settingsField['type'] === 'select') {
                    $sanitizedInput[$fieldKey] = sanitize_text_field($input[$fieldKey]);
                }
                if ($settingsField['type'] === 'callback') {
                    $sanitizedInput[$fieldKey] = call_user_func_array($settingsField['sanitize_callback'], [$input[$fieldKey]]);
                }
            }
        }

        return $sanitizedInput;
    }

    /**
     * Print the Section text.
     */
    public function print_section_info()
    {
        echo 'Enter your settings below:';
    }

    /**
     * Catch callbacks for creating setting fields.
     *
     * @param string $function
     * @param array  $arguments
     *
     * @return bool
     */
    public function __call($function, $arguments)
    {
        if (!isset($this->settingsFields[$function])) {
            return false;
        }

        $field = $this->settingsFields[$function];
        $this->create_settings_field($field, $function);

        return true;
    }

    public function get_setting_value($settingKey)
    {
        if (!$this->settingsValues) {
            $this->settingsValues = get_option(self::SETTINGS_KEY);
        }

        if (isset($this->settingsValues[$settingKey]) && !empty($this->settingsValues[$settingKey])) {
            return $this->settingsValues[$settingKey];
        }

        return false;
    }

    private function get_select_field_options($field)
    {
        if (isset($field['options_callback'])) {
            $options = $this->{$field['options_callback']}($field['locale']);
            if ($options) {
                return $options;
            }
        }

        return [];
    }

    private function create_settings_field($field, $fieldKey)
    {
        $fieldName = self::SETTINGS_KEY."[$fieldKey]";
        $fieldOutput = false;

        if ($field['type'] === 'text') {
            $fieldValue = isset($this->settingsValues[$fieldKey]) ? esc_attr($this->settingsValues[$fieldKey]) : '';
            $fieldOutput = "<input type='text' name='$fieldName' value='$fieldValue' class='regular-text' />";
        }
        if ($field['type'] === 'checkbox') {
            $checked = isset($this->settingsValues[$fieldKey]) && $this->settingsValues[$fieldKey] ? 'checked' : '';
            $fieldOutput = "<input type='hidden' value='0' name='$fieldName'>";
            $fieldOutput .= "<input type='checkbox' value='1' name='$fieldName' $checked />";
        }
        if ($field['type'] === 'select') {
            $fieldValue = isset($this->settingsValues[$fieldKey]) ? $this->settingsValues[$fieldKey] : '';
            $fieldOutput = "<select name='$fieldName'>";
            $options = $this->get_select_field_options($field);
            foreach ($options as $option) {
                $selected = ($option['system_key'] === $fieldValue) ? 'selected' : '';
                $fieldOutput .= "<option value='".$option['system_key']."' $selected >".$option['system_key'].'</option>';
            }
            $fieldOutput .= '</select>';
        }
        if ($field['type'] === 'callback') {
            $fieldValue = isset($this->settingsValues[$fieldKey]) ? $this->settingsValues[$fieldKey] : [];

            call_user_func_array($field['callback'], [$fieldName, $fieldValue]);
        }

        if ($fieldOutput) {
            echo $fieldOutput;
        }
    }
}
