<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTenantLayoutDefaultsRequest extends FormRequest
{
    /**
     * The name of the error bag.
     *
     * @var string
     */
    protected $errorBag = 'tenantLayoutDefaults';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && method_exists($user, 'isOwner') && $user->isOwner();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'theme' => ['required', 'string', Rule::in(array_keys(config('themes.presets', [])))],
            'sidebar_position' => ['required', 'string', Rule::in($this->allowedValues('sidebar_position'))],
            'topbar_behavior' => ['required', 'string', Rule::in($this->allowedValues('topbar_behavior'))],
            'topbar_style' => ['required', 'string', Rule::in($this->allowedValues('topbar_style'))],
            'sidebar_style' => ['required', 'string', Rule::in($this->allowedValues('sidebar_style'))],
            'color_mode' => ['required', 'string', Rule::in($this->allowedValues('color_mode'))],
            'font_size' => ['required', 'string', Rule::in($this->allowedValues('font_size'))],
            'border_radius' => ['required', 'string', Rule::in($this->allowedValues('border_radius'))],
            'logo_visibility' => ['required', 'boolean'],
            'dashboard_widget_order' => ['required', 'array'],
            'dashboard_widget_order.*' => ['required', 'string', Rule::in(array_keys(config('layout.widgets', [])))],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'theme.required' => 'Choose a workspace theme color.',
            'sidebar_position.required' => 'Choose where the sidebar should appear.',
            'topbar_behavior.required' => 'Choose how the topbar should behave while scrolling.',
            'topbar_style.required' => 'Choose a topbar style for the workspace.',
            'sidebar_style.required' => 'Choose a sidebar style for the workspace.',
            'color_mode.required' => 'Choose the default color mode.',
            'font_size.required' => 'Choose the default font size.',
            'border_radius.required' => 'Choose the default border radius.',
            'logo_visibility.required' => 'Choose whether the workspace logo should be shown.',
            'dashboard_widget_order.required' => 'Arrange all workspace dashboard widgets before saving.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $widgetOrder = $this->input('dashboard_widget_order', []);

                if (! is_array($widgetOrder)) {
                    return;
                }

                if (count($widgetOrder) !== count(array_unique($widgetOrder))) {
                    $validator->errors()->add('dashboard_widget_order', 'Choose each dashboard widget only once.');
                }

                $expected = array_keys(config('layout.widgets', []));
                $missing = array_diff($expected, $widgetOrder);
                $unexpected = array_diff($widgetOrder, $expected);

                if ($missing !== [] || $unexpected !== []) {
                    $validator->errors()->add('dashboard_widget_order', 'The workspace widget order must include every dashboard widget exactly once.');
                }
            },
        ];
    }

    /**
     * Get the allowed values for a layout setting.
     *
     * @return array<int, string>
     */
    private function allowedValues(string $setting): array
    {
        return array_keys(config("layout.options.{$setting}", []));
    }
}
