Have you ever built a lengthy registration form or checkout process that made users hesitate before completing it? The solution often lies in breaking down complex forms into manageable steps using a step wizard. And with Livewire 4’s latest release, creating smooth, animated step wizards has never been easier.
In this article, we’ll explore how to build an elegant step wizard using Livewire 4’s wire:transition feature. This leverages the browser's native View Transitions API, which is hardware-accelerated for butter-smooth animations without the overhead of JavaScript libraries.

Why Wire Transition?
Before diving into code, let’s understand what makes wire:transition a game-changer:
Hardware-Accelerated Performance: Unlike traditional JavaScript animation libraries, View Transitions API is handled natively by the browser, resulting in smoother animations with better performance.
Developer-Friendly: Simply add the wire:transition directive to any element. No complex JavaScript setup required.
Highly Customizable: While easy to implement, you maintain full control over animations through CSS.
Accessibility-First: Livewire automatically respects the user’s prefers-reduced-motion setting, ensuring a comfortable experience for motion-sensitive users.
Press enter or click to view image in full size

Getting Started
First, ensure you have Livewire 4 installed in your Laravel project:
composer require livewire/livewire:^4.0
Building the Wizard Component
We’ll create a three-step registration wizard covering User Information, Authentication, and Preview stages.
1. Create a Livewire component
Livewire provides a convenient Artisan command to generate new components. Run the following command to make a new page component:
php artisan make:livewire pages::izard
Since this component will be used as a full page, we use the pages:: prefix to keep it organized in the pages directory.
This command will generate a new single-file component at resources/views/pages/⚡wizard.blade.php.
<?phpuse Livewire\Component;
use Livewire\Attributes\Transition;new class extends Component {
    public $step = 1;
    public $totalSteps = 3;    
    public $name;
    public $email;
    public $phone;    
    public $username;
    public $password;
    public $password_confirmation;    #[Transition(type: 'forward')]
    public function nextStep()
    {
        
        $this->validateCurrentStep();
        if ($this->step == 3) {
            $this->save();
            return;
        }
        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }    #[Transition(type: 'backward')]
    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }    protected function validateCurrentStep()
    {
        if ($this->step == 1) {
            $this->validate([
                'name' => 'required|min:3',
                'email' => 'required|email',
                'phone' => 'required',
            ]);
        }
        if ($this->step == 2) {
            $this->validate([
                'username' => 'required|min:3',
                'password' => 'required|min:8',
                'password_confirmation' => 'required|same:password',
            ]);
        }
    }    protected function save()
    {
        
        session()->flash('message', 'Registration successful!');
        $this->reset();
    }    public function render()
    {
        return $this->view()->title('Registration Wizard');
    }
};
?>
Notice the #[Transition] attributes. This is Livewire 4's way of defining different transition types for each method. The nextStep() method uses 'forward' type, while previousStep() uses 'backward'. This allows us to create directional animations.
2. Build the Blade Template
Add this Blade template after the PHP section in the same file:
<div class="max-w-2xl mx-auto p-6">
    
    <div class="mb-8">
        <ul class="flex items-center justify-between">
            @for ($i = 1; $i <= $totalSteps; $i++)
            <li class="flex-1 flex items-center">
                <div class="flex items-center w-full">
                    <div class="flex flex-col items-center">
                        <span class="w-10 h-10 flex items-center justify-center rounded-full
                            {{ $step >= $i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}
                            font-semibold transition-colors duration-300">
                            {{ $i }}
                        </span>
                        <span class="text-xs mt-2 text-gray-600">
                            @if($i == 1) Info
                            @elseif($i == 2) Auth
                            @else Preview
                            @endif
                        </span>
                    </div>
                    @if ($i < $totalSteps)
                    <div class="flex-1 h-1 mx-4
                        {{ $step > $i ? 'bg-blue-600' : 'bg-gray-200' }}
                        transition-colors duration-300">
                    </div>
                    @endif
                </div>
            </li>
            @endfor
        </ul>
    </div>    
    <div class="bg-white rounded-lg shadow-md p-6 min-h-[400px]">
        @if ($step == 1)
          <div wire:transition="step">
              <h2 class="text-2xl font-bold mb-6 text-gray-800">Personal Information</h2>
              <div class="space-y-4">
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                      <input type="text" wire:model="name"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('name') border-red-500 @enderror">
                      @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                  </div>
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                      <input type="email" wire:model="email"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('email') border-red-500 @enderror">
                      @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                  </div>
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                      <input type="text" wire:model="phone"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('phone') border-red-500 @enderror">
                      @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                  </div>
              </div>
          </div>
        @endif        @if ($step == 2)
          <div wire:transition="step">
              <h2 class="text-2xl font-bold mb-6 text-gray-800">Account Security</h2>
              <div class="space-y-4">
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                      <input type="text" wire:model="username"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('username') border-red-500 @enderror">
                      @error('username') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                  </div>
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                      <input type="password" wire:model="password"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('password') border-red-500 @enderror">
                      @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                  </div>
                  <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                      <input type="password" wire:model="password_confirmation"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('password_confirmation') border-red-500 @enderror">
                      @error('password_confirmation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                  </div>
              </div>
          </div>
        @endif        @if ($step == 3)
          <div wire:transition="step">
              <h2 class="text-2xl font-bold mb-6 text-gray-800">Review & Confirm</h2>
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                  <p class="text-blue-800 text-sm">Please review your information before submitting.</p>
              </div>
              <div class="space-y-4">
                  <div class="grid grid-cols-2 gap-4">
                      <div>
                          <p class="text-sm text-gray-500">Full Name</p>
                          <p class="font-semibold text-gray-800">{{ $name }}</p>
                      </div>
                      <div>
                          <p class="text-sm text-gray-500">Email</p>
                          <p class="font-semibold text-gray-800">{{ $email }}</p>
                      </div>
                      <div>
                          <p class="text-sm text-gray-500">Phone</p>
                          <p class="font-semibold text-gray-800">{{ $phone }}</p>
                      </div>
                      <div>
                          <p class="text-sm text-gray-500">Username</p>
                          <p class="font-semibold text-gray-800">{{ $username }}</p>
                      </div>
                  </div>
              </div>
          </div>
        @endif
    </div>    
    <div class="flex justify-between mt-6">
        <button wire:click="previousStep"
            {{ $step == 1 ? 'disabled' : '' }}
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300
            disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            ← Previous
        </button>
        <button wire:click="nextStep"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50"
            class="px-6 py-2 rounded-lg text-white transition-colors
            {{ $step == $totalSteps ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' }}">
            <span wire:loading.remove>
                {{ $step == $totalSteps ? 'Submit' : 'Next →' }}
            </span>
            <span wire:loading>Processing...</span>
        </button>
    </div>
</div>
The key here is wire:transition="step". This tells Livewire to apply transitions to this element using the name "step", which we'll target in our CSS for custom animations.
3. Add Custom CSS Animations
Now for the exciting part — creating directional slide animations. Add this CSS to your layout or as a <style> section:
html:active-view-transition-type(forward) {
    &::view-transition-old(step) {
        animation: 500ms ease-out slide-out-left;
    }    &::view-transition-new(step) {
        animation: 500ms ease-in slide-in-right;
    }
}html:active-view-transition-type(backward) {
    &::view-transition-old(step) {
        animation: 500ms ease-out slide-out-right;
    }
    &::view-transition-new(step) {
        animation: 500ms ease-in slide-in-left;
    }
}@keyframes slide-out-left {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(-100%);
        opacity: 0;
    }
}@keyframes slide-in-right {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}@keyframes slide-out-right {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}@keyframes slide-in-left {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
Understanding the CSS:
The :active-view-transition-type(forward) and (backward) selectors target transitions based on the type we set in the #[Transition] attribute.
::view-transition-old(step) captures the snapshot of the outgoing element.
::view-transition-new(step) captures the snapshot of the incoming element.
For forward transitions, the old element slides left while the new element slides in from the right.
For backward transitions, it’s reversed: the old element slides right while the new element slides in from the left.
Alternative Animation Styles
Beyond sliding, you can create various animation effects. Here are some examples:
Simple Fade Transition
html:active-view-transition-type(forward) {
    &::view-transition-old(step) {
        animation: 300ms ease-out fade-out;
    }    &::view-transition-new(step) {
        animation: 300ms ease-in fade-in;
    }
}@keyframes fade-out {
    to { opacity: 0; }
}@keyframes fade-in {
    from { opacity: 0; }
}
Scale Animation
html:active-view-transition-type(forward) {
  &::view-transition-old(step) {
      animation: 400ms ease-out scale-down;
  }  &::view-transition-new(step) {
        animation: 400ms ease-in scale-up;
    }
}@keyframes scale-down {
    to { 
        transform: scale(0.8);
        opacity: 0;
    }
}@keyframes scale-up {
  from { 
      transform: scale(1.2);
      opacity: 0;
  }
}
Rotate and Fade
html:active-view-transition-type(forward) {
    &::view-transition-old(step) {
        animation: 500ms ease-out rotate-fade-out;
    }    &::view-transition-new(step) {
        animation: 500ms ease-in rotate-fade-in;
    }
}@keyframes rotate-fade-out {
    to { 
        transform: rotate(-10deg);
        opacity: 0;
    }
}@keyframes rotate-fade-in {
    from { 
        transform: rotate(10deg);
        opacity: 0;
    }
}
Advanced Tips and Tricks
1. Skipping Transitions
Sometimes you need actions that jump immediately without animation, like a “Reset” button:
public function reset()
{
    $this->skipTransition();
    $this->step = 1;
    $this->reset(['name', 'email', 'phone', 'username', 'password']);
}
Or use the attribute:
#[Transition(skip: true)]
public function reset()
{
    $this->step = 1;
    $this->reset(['name', 'email', 'phone', 'username', 'password']);
}
2. Conditional Navigation
Prevent users from skipping steps:
public function goToStep($targetStep)
{
    
    if ($targetStep <= $this->step + 1) {
        $type = $targetStep > $this->step ? 'forward' : 'backward';
        $this->transition(type: $type);
        $this->step = $targetStep;
    }
}
3. Progress Persistence
Save progress to the session:
public function updated($property)
{
    session()->put('wizard_data', [
        'step' => $this->step,
        'name' => $this->name,
        'email' => $this->email,
        // ... other properties
    ]);
}public function mount()
{
    $data = session('wizard_data', []);
    $this->fill($data);
}
4. Dynamic Step Validation
Create a method to handle validation for each step:
protected function rules()
{
    $rules = [
        1 => [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|regex:/^[0-9]{10,15}$/',
        ],
        2 => [
            'username' => 'required|min:3|unique:users,username',
            'password' => 'required|min:8|regex:/[a-zA-Z]/|regex:/[0-9]/',
            'password_confirmation' => 'required|same:password',
        ],
    ];    return $rules[$this->step] ?? [];
}
Browser Support and Fallbacks
View Transitions API is supported in:
Chrome 111+
Edge 111+
Safari 18+
Firefox 144+ (limited support, no transition types)
For unsupported browsers, elements will still appear and disappear — just without the smooth animation. The functionality remains intact, ensuring a consistent user experience across all browsers.
Performance Considerations
Since View Transitions are hardware-accelerated, they perform exceptionally well. However, keep these tips in mind:
Minimize DOM Complexity: Simpler DOM structures animate more smoothly.
Avoid Excessive Nesting: Deep nesting can impact transition performance.
Use Transform and Opacity: These properties are optimized for hardware acceleration.
Test on Real Devices: Always test animations on actual mobile devices, not just desktop browsers.
Real-World Use Cases
This step wizard pattern works excellently for:
Multi-step registration forms with email verification
E-commerce checkout processes with cart, shipping, and payment steps
Onboarding flows for new users
Configuration wizards for complex setups
Survey and questionnaire forms with progress tracking
Profile completion flows with multiple sections
Conclusion
Livewire 4’s wire:transition feature brings native, hardware-accelerated animations to your Laravel applications without the complexity of JavaScript libraries. By leveraging the View Transitions API, you can create smooth, professional step wizards that enhance user experience while maintaining clean, maintainable code.
The combination of Livewire’s reactive components and browser-native animations represents a significant leap forward in building modern web applications. No more wrestling with jQuery plugins or complex animation libraries — just clean, declarative code that works.
For complete documentation on wire:transition and other Livewire 4 features, visit the official Livewire documentation(https://livewire.laravel.com/docs/4.x/wire-transition).
Ready to build your own step wizard? Start experimenting with different animations and see how wire:transition can transform your forms from functional to fantastic!