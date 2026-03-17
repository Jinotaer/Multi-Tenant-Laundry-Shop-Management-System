<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserLayoutPreferencesRequest extends FormRequest
{
    /**
     * The name of the error bag.
     *
     * @var string
     */
    protected $errorBag = 'userLayoutPreferences';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && (! method_exists($user, 'isOwner') || ! $user->isOwner());
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
            'dashboard_widget_order' => ['nullable', 'array'],
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
            'theme.required' => 'Choose a theme color for your personal dashboard.',
            'sidebar_position.required' => 'Choose where you want your sidebar to appear.',
            'topbar_behavior.required' => 'Choose how your topbar should behave while scrolling.',
            'topbar_style.required' => 'Choose a topbar style for your dashboard.',
            'sidebar_style.required' => 'Choose your sidebar style.',
            'color_mode.required' => 'Choose a color mode for your dashboard.',
            'font_size.required' => 'Choose your font size preference.',
            'border_radius.required' => 'Choose your border radius preference.',
            'logo_visibility.required' => 'Choose whether you want the logo to be shown.',
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
                $widgetOrder = $this->input('dashboard_widget_order');

                if ($widgetOrder === null) {
                    return;
                }

                if (! $this->canCustomizeWidgetOrder()) {
                    $validator->errors()->add('dashboard_widget_order', 'Dashboard widget ordering is only available for staff accounts.');

                    return;
                }

                if (! is_array($widgetOrder)) {
                    return;
                }

                if (count($widgetOrder) !== count(array_unique($widgetOrder))) {
                    $validator->errors()->add('dashboard_widget_order', 'Choose each dashboard widget only once.');
                }

                $allowedWidgets = $this->allowedWidgetKeys();
                $missing = array_diff($allowedWidgets, $widgetOrder);
                $unexpected = array_diff($widgetOrder, $allowedWidgets);

                if ($missing !== [] || $unexpected !== []) {
                    $validator->errors()->add('dashboard_widget_order', 'Your widget order must include each dashboard widget available to your role exactly once.');
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

    /**
     * Determine if the current user can customize widget ordering.
     */
    private function canCustomizeWidgetOrder(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return method_exists($user, 'isStaff') && $user->isStaff();
    }

    /**
     * Get the widget keys available to the current user's role.
     *
     * @return array<int, string>
     */
    private function allowedWidgetKeys(): array
    {
        $user = $this->user();

        if ($user === null) {
            return [];
        }

        $role = $user->isStaff() ? 'staff' : 'customer';

        return array_keys(array_filter(
            config('layout.widgets', []),
            fn (array $widget): bool => in_array($role, $widget['roles'] ?? [], true),
        ));
    }
}
