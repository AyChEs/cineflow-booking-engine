@extends('layout')
@section('title', 'Checkout – Step 3: Payment')
@section('content')

@include('compra._progress', ['step' => 3])

{{-- 3D card for the simulated form: perspective, flip and chip gradients. --}}
<style>
/* 3D card */
.card-scene {
    width: 340px; height: 195px;
    perspective: 1000px;
    margin: 0 auto 1.75rem;
}
.card-3d {
    width: 100%; height: 100%;
    position: relative;
    transform-style: preserve-3d;
    transition: transform 0.65s cubic-bezier(.4,0,.2,1);
}
.card-3d.flipped { transform: rotateY(180deg); }
.card-face {
    position: absolute; inset: 0;
    border-radius: 16px;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    box-shadow: 0 22px 56px rgba(0,0,0,.38);
}
.card-front {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 55%, #1a3050 100%);
    padding: 1.25rem 1.4rem;
    color: #fff;
    display: flex; flex-direction: column; justify-content: space-between;
}
.card-back {
    background: linear-gradient(135deg, #14213d 0%, #1a2a4a 100%);
    transform: rotateY(180deg);
    border-radius: 16px;
    overflow: hidden;
    display: flex; flex-direction: column; justify-content: center;
}
.card-stripe { background: #111; height: 46px; margin-top: 36px; }
.card-cvv-area {
    background: #f3f4f6; margin: 0.75rem 1.25rem;
    border-radius: 4px; padding: 0.35rem 0.75rem;
    display: flex; justify-content: flex-end; align-items: center; gap: 0.5rem;
}
.card-chip {
    width: 40px; height: 28px;
    background: linear-gradient(135deg, #d4a843, #f7e08a, #c49020);
    border-radius: 4px;
    position: relative;
}
.card-chip::before {
    content: '';
    position: absolute; inset: 4px 5px;
    background: linear-gradient(160deg, #e8c255, #f5df90);
    border-radius: 2px;
    border: 0.5px solid rgba(0,0,0,.15);
}
.card-chip::after {
    content: '';
    position: absolute; top: 50%; left: 0; right: 0;
    height: 1px; background: rgba(0,0,0,.15);
}
.card-num-display {
    font-family: 'Courier New', monospace;
    font-size: 1.25rem; letter-spacing: 4px;
    text-align: center; text-shadow: 0 1px 4px rgba(0,0,0,.4);
}
.card-info-row { display: flex; justify-content: space-between; align-items: flex-end; }
.card-info-item span.lbl { font-size: 0.52rem; opacity: 0.65; text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 2px; }
.card-info-item span.val { font-size: 0.82rem; letter-spacing: 1px; }
/* Payment method */
.method-btn {
    flex: 1; padding: 0.85rem 0.5rem;
    border: 2px solid #e5e7eb; border-radius: 12px;
    background: #fff; cursor: pointer;
    display: flex; flex-direction: column; align-items: center; gap: 0.35rem;
    font: 500 0.82rem/1.3 inherit; color: #374151;
    transition: border-color .2s, box-shadow .2s, background .2s;
}
.method-btn:hover { border-color: #9ca3af; box-shadow: 0 3px 10px rgba(0,0,0,.08); }
.method-btn.active { border-color: #1e3a5f; background: #eef4fb; box-shadow: 0 3px 14px rgba(30,58,95,.18); }
/* Inputs */
.pay-input {
    width: 100%; border: 1.5px solid #e0e0e0; border-radius: 9px;
    padding: 0.65rem 0.9rem; font: 0.95rem/1 inherit; outline: none;
    transition: border-color .2s, box-shadow .2s; background: #fafafa;
    box-sizing: border-box;
    color: #111827; /* always dark, regardless of the parent's background */
}
.pay-input:focus { border-color: #1e3a5f; box-shadow: 0 0 0 3px rgba(30,58,95,.1); background: #fff; }
.pay-label { font: 500 0.75rem/1 inherit; color: #6b7280; display: block; margin-bottom: 0.38rem; }
.pay-field { margin-bottom: 1rem; }
/* step3 layout */
.step3-container {
    display: grid;
    grid-template-columns: minmax(0,1fr) 340px;
    gap: 2rem;
    align-items: start;
    max-width: 64rem;
    margin: 2.5rem auto;
    padding: 0 1.5rem;
}
@media (max-width: 1024px) {
    .step3-container { grid-template-columns: 1fr; }
    .compra-sidebar  { position: static !important; }
}
@media (max-width: 640px) {
    .step3-container { padding: 0 1rem; margin: 1.25rem auto; gap: 1.25rem; }
    .step3-main      { padding: 1.5rem 1.25rem !important; }
    /* adapted 3D card */
    .card-scene      { width: 100% !important; max-width: 300px; height: 170px !important; }
    .card-num-display { font-size: 1rem !important; letter-spacing: 2px !important; }
    /* name/surname in a single column on mobile */
    .personal-grid   { grid-template-columns: 1fr !important; }
    /* payment methods in a column */
    .method-row      { flex-direction: column !important; }
    /* full-width payment method buttons */
    .method-btn      { flex: unset !important; width: 100%; flex-direction: row !important; gap: 0.75rem !important; }
}
/* Saved card */
.saved-option {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.8rem 1rem; border-radius: 10px; cursor: pointer;
    border: 1.5px solid #e5e7eb;
    transition: border-color .15s, background .15s;
    margin-bottom: 0.5rem;
}
.saved-option:has(input:checked) { border-color: #1e3a5f; background: #eef4fb; }
/* Embedded Stripe payment panel */
.stripe-pay-card {
    border: 1.5px solid #e5e7eb; border-radius: 16px;
    padding: 1.5rem 1.4rem 1.25rem;
    background: linear-gradient(180deg, #fbfcfe 0%, #ffffff 60%);
    box-shadow: 0 4px 20px rgba(30,58,95,.06);
}
.stripe-pay-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.1rem;
}
.stripe-pay-badge {
    display: inline-flex; align-items: center; gap: 0.4rem;
    background: #eaf3ea; color: #1a7a3f;
    font: 700 0.72rem/1 inherit; letter-spacing: .3px;
    padding: 0.4rem 0.7rem; border-radius: 999px;
}
.stripe-pay-brands { display: flex; gap: 0.5rem; font-size: 1.5rem; line-height: 1; }
.stripe-pay-testcard {
    margin-top: 1rem; text-align: center;
    background: #f4f7fb; border: 1px dashed #c7d6e8;
    color: #4b5b70; font: 0.72rem/1.4 'Courier New', monospace;
    padding: 0.55rem 0.75rem; border-radius: 10px;
}
</style>

<div class="step3-container">

    {{-- MAIN COLUMN --}}
    <div class="step3-main bg-white rounded-xl shadow-sm" style="padding: 2rem;">

        @if(session('error'))
            <div class="bg-red-50 border border-red-300 text-red-600 px-4 py-3 rounded-lg mb-6 text-sm">
                <i class="fas fa-exclamation-circle mr-1.5"></i>{{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('compra.step3.store') }}" id="payForm">
            @csrf

            {{-- PERSONAL DETAILS --}}
            <h2 class="text-[#1e3a5f] text-base font-bold mb-5 pb-2 border-b-2 border-gray-100
                        flex items-center gap-2">
                <i class="fas fa-user-circle text-red-600"></i> Personal information
            </h2>

            <div class="personal-grid grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="pay-label">First name(s) <span class="text-red-600">*</span></label>
                    <input type="text" name="nom" class="pay-input"
                        value="{{ old('nom', $user?->name ?? '') }}" placeholder="Your first name" required>
                    @error('nom')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="pay-label">Last name</label>
                    <input type="text" name="cognoms" class="pay-input"
                        value="{{ old('cognoms', $user?->apellidos ?? '') }}" placeholder="Your last name">
                </div>
            </div>

            <div class="mb-8">
                <label class="pay-label">Email address <span class="text-red-600">*</span></label>
                <input type="email" name="email" class="pay-input"
                    value="{{ old('email', $user?->email ?? '') }}" placeholder="email@example.com" required>
                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- PAYMENT METHOD --}}
            <h2 class="text-[#1e3a5f] text-base font-bold mb-5 pb-2 border-b-2 border-gray-100
                        flex items-center gap-2">
                <i class="fas fa-credit-card text-red-600"></i> Payment method
            </h2>

            <input type="hidden" name="metode" id="metodeInput" value="targeta">

            {{-- CARD PANEL --}}
            <div id="panelTargeta">

                @if($stripeEnabled)
                {{-- Embedded payment with Stripe Payment Element (test mode) — without leaving the site --}}
                <div class="stripe-pay-card">
                    <div class="stripe-pay-head">
                        <span class="stripe-pay-badge">
                            <i class="fas fa-lock"></i> Secure payment
                        </span>
                        <span class="stripe-pay-brands">
                            <i class="fab fa-cc-visa text-[#1a1f71]"></i>
                            <i class="fab fa-cc-mastercard text-[#eb001b]"></i>
                            <i class="fab fa-cc-amex text-[#007bc1]"></i>
                        </span>
                    </div>

                    {{-- Stripe mounts the card form here (Payment Element) --}}
                    <div id="stripe-payment-element" class="mb-1"></div>

                    {{-- Payment error messages --}}
                    <div id="stripe-errors" class="hidden text-red-600 text-sm mt-3 flex items-start gap-1.5">
                        <i class="fas fa-exclamation-circle mt-0.5"></i><span id="stripe-errors-text"></span>
                    </div>

                    <div class="stripe-pay-testcard">
                        Test mode → card <strong>4242 4242 4242 4242</strong> · any future date · any CVC
                    </div>
                    <p class="text-xs text-gray-400 text-center mt-3">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Data encrypted and processed by Stripe. CineFlow never stores it.
                    </p>
                </div>
                @else

                {{-- Saved cards (if the user has one saved) --}}
                @if($savedCard)
                <div class="mb-5">
                    <p class="text-xs text-gray-500 font-semibold mb-2 uppercase tracking-wide">Your saved methods</p>
                    <label class="saved-option">
                        <input type="radio" name="card_mode" value="saved" onchange="toggleCardMode('saved')"
                            class="accent-[#1e3a5f] w-4 h-4"
                            {{ old('card_mode', 'saved') === 'saved' ? 'checked' : '' }}>
                        <span class="text-xl">💳</span>
                        <div class="flex-1">
                            <div class="font-semibold text-sm">{{ $savedCard }}</div>
                            <div class="text-xs text-gray-400">Saved card•••</div>
                        </div>
                        <span class="bg-[#1e3a5f] text-white rounded-md px-2 py-0.5 text-xs font-bold">Use</span>
                    </label>
                    <label class="saved-option">
                        <input type="radio" name="card_mode" value="new" onchange="toggleCardMode('new')"
                            class="accent-[#1e3a5f] w-4 h-4"
                            {{ old('card_mode') === 'new' ? 'checked' : '' }}>
                        <span class="text-lg">➕</span>
                        <div>
                            <div class="font-semibold text-sm">Use another card</div>
                            <div class="text-xs text-gray-400">Enter a different card</div>
                        </div>
                    </label>
                    <input type="hidden" name="card_mode" id="cardModeHidden"
                        value="{{ old('card_mode', 'saved') }}">
                </div>
                @endif

                {{-- New card form with 3D preview --}}
                <div id="newCardForm"
                    class="{{ $savedCard && old('card_mode', 'saved') === 'saved' ? 'hidden' : '' }}">

                    {{-- Animated preview — uses the classes from the <style> block above --}}
                    <div class="card-scene">
                        <div class="card-3d" id="card3d">
                            <div class="card-face card-front" id="cardFront">
                                <div class="flex justify-between items-center">
                                    <div class="card-chip"></div>
                                    <span id="cardBrandLbl" class="text-[0.7rem] font-bold tracking-[1px] opacity-85">VISA</span>
                                </div>
                                <div class="card-num-display" id="cardNumDisplay">
                                    **** &nbsp;**** &nbsp;**** &nbsp;****
                                </div>
                                <div class="card-info-row">
                                    <div class="card-info-item">
                                        <span class="lbl">Cardholder</span>
                                        <span class="val" id="cardNameDisplay">FULL NAME</span>
                                    </div>
                                    <div class="card-info-item text-right">
                                        <span class="lbl">Valid thru</span>
                                        <span class="val" id="cardExpiryDisplay">MM/YY</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-face card-back">
                                <div class="card-stripe"></div>
                                <div class="card-cvv-area">
                                    <span class="text-[0.65rem] text-gray-400 mr-1.5">CVV</span>
                                    <span id="cardCvvDisplay" class="font-mono text-[1.05rem] text-gray-700 tracking-[3px]">•••</span>
                                </div>
                                <p class="text-right text-[0.58rem] text-gray-600 px-5 mt-2 italic">
                                    CineFlow S.L. — Payment simulation
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Card number --}}
                    <div class="pay-field">
                        <label class="pay-label">Card number</label>
                        <div class="relative">
                            <input type="text" name="num_targeta" id="cardNumInput" class="pay-input pr-12 tracking-[2px] font-mono"
                                placeholder="1234  5678  9012  3456"
                                maxlength="19" inputmode="numeric" autocomplete="cc-number"
                                value="{{ old('num_targeta') }}">
                            <span id="cardBrandIcon"
                                  class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xl pointer-events-none">💳</span>
                        </div>
                        @error('num_targeta')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Cardholder --}}
                    <div class="pay-field">
                        <label class="pay-label">Cardholder name</label>
                        <input type="text" name="titular_targeta" id="cardNameInput" class="pay-input uppercase"
                            placeholder="As it appears on the card"
                            autocomplete="cc-name" value="{{ old('titular_targeta') }}">
                        @error('titular_targeta')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Expiry and CVV in two columns --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="pay-label">Expiry date</label>
                            <input type="text" name="expiry_targeta" id="cardExpiryInput" class="pay-input"
                                placeholder="MM/YY" maxlength="5" inputmode="numeric" autocomplete="cc-exp"
                                value="{{ old('expiry_targeta') }}">
                            @error('expiry_targeta')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="pay-label">CVV <span class="text-gray-400 font-normal">(3–4 digits)</span></label>
                            <input type="text" name="cvv_targeta" id="cardCvvInput" class="pay-input"
                                placeholder="•••" maxlength="4" inputmode="numeric" autocomplete="cc-csc"
                                value="{{ old('cvv_targeta') }}">
                            @error('cvv_targeta')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Save card (authenticated users only) --}}
                    @auth
                    <div class="flex items-start gap-3 p-3.5 bg-slate-50 border-[1.5px] border-slate-200 rounded-xl mb-2">
                        <input type="checkbox" name="guardar_targeta" id="guardarTargeta" value="1"
                            class="w-[17px] h-[17px] accent-[#1e3a5f] shrink-0 mt-0.5"
                            {{ old('guardar_targeta') ? 'checked' : '' }}>
                        <label for="guardarTargeta" class="cursor-pointer text-sm text-gray-700 leading-relaxed">
                            <strong>Save card</strong> for future purchases
                            <span class="block text-xs text-gray-400 mt-0.5">
                                <i class="fas fa-lock mr-1"></i>We only securely store the last 4 digits.
                            </span>
                        </label>
                    </div>
                    @endauth

                </div>{{-- /newCardForm --}}
                @endif{{-- /stripeEnabled --}}

            </div>{{-- /panelTargeta --}}

            {{-- TERMS AND CONDITIONS --}}
            <div class="flex items-start gap-3 my-7 p-4 bg-gray-50 border-[1.5px] border-gray-200 rounded-xl">
                <input type="checkbox" id="terms" name="terms" required
                    class="mt-0.5 w-[17px] h-[17px] shrink-0 accent-[#1e3a5f]">
                <label for="terms" class="text-sm text-gray-500 leading-relaxed cursor-pointer">
                    I have read and agree to the
                    <a href="#" class="text-[#1e3a5f] font-semibold">Privacy Policy</a>
                    and the <a href="#" class="text-[#1e3a5f] font-semibold">Legal Notice</a>.
                    No changes or refunds will be made once the transaction is complete.
                </label>
            </div>

            {{-- PAY BUTTON --}}
            <button type="submit" id="btnRealitzar"
                class="w-full bg-gray-300 text-white border-none rounded-full py-3.5
                       text-base font-bold tracking-[2px] uppercase cursor-not-allowed
                       transition-all duration-200"
                disabled>
                <i class="fas fa-lock mr-2"></i>PAY NOW
            </button>

            {{-- Security icons and accepted payment methods --}}
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 mt-4
                        text-xs text-gray-400">
                <span><i class="fas fa-shield-alt mr-1"></i>100% secure payment · SSL</span>
                <span>·</span>
                <i class="fab fa-cc-visa text-lg text-[#1a1f71]"></i>
                <i class="fab fa-cc-mastercard text-lg text-[#eb001b]"></i>
                <i class="fab fa-cc-amex text-lg text-[#007bc1]"></i>
                <span>·</span>
                <span><i class="fab fa-google-pay text-lg mr-1"></i>Google Pay</span>
            </div>

        </form>
    </div>

    {{-- SIDEBAR --}}
    @include('compra._sidebar', ['sesion' => $sesion, 'step' => 3, 'compra' => $compra])
</div>

@if($stripeEnabled)
{{-- EMBEDDED STRIPE FLOW (Payment Element) — payment happens without leaving the site --}}
<script src="https://js.stripe.com/v3/"></script>
<script>
(function () {
    const pk       = @json(config('services.stripe.key'));
    const amount   = {{ (int) round($compra['total'] * 100) }};
    const currency = @json(config('services.stripe.currency', 'eur'));
    const intentUrl = @json(route('compra.stripe.intent'));
    const returnUrl = @json(route('compra.stripe.return'));
    const csrf     = document.querySelector('input[name="_token"]').value;

    const form      = document.getElementById('payForm');
    const btn       = document.getElementById('btnRealitzar');
    const terms     = document.getElementById('terms');
    const errBox    = document.getElementById('stripe-errors');
    const errText   = document.getElementById('stripe-errors-text');

    const stripe = Stripe(pk);
    // Deferred mode: we create the Payment Element without a client_secret and generate the
    // PaymentIntent on the server right when the payment is confirmed.
    const elements = stripe.elements({
        mode: 'payment',
        amount: amount,
        currency: currency,
        appearance: {
            theme: 'stripe',
            variables: {
                colorPrimary: '#1e3a5f',
                colorText: '#1f2937',
                borderRadius: '10px',
                fontFamily: 'Figtree, system-ui, sans-serif',
            },
        },
    });
    const paymentElement = elements.create('payment', { layout: 'tabs' });
    paymentElement.mount('#stripe-payment-element');

    // Enable/disable the button based on the terms checkbox
    function refreshBtn() {
        const ok = terms.checked;
        btn.disabled = !ok;
        if (ok) {
            btn.classList.remove('bg-gray-300', 'cursor-not-allowed');
            btn.classList.add('bg-[#1e3a5f]', 'hover:bg-[#152a45]', 'cursor-pointer', 'shadow-[0_6px_20px_rgba(30,58,95,.35)]');
        } else {
            btn.classList.remove('bg-[#1e3a5f]', 'hover:bg-[#152a45]', 'cursor-pointer', 'shadow-[0_6px_20px_rgba(30,58,95,.35)]');
            btn.classList.add('bg-gray-300', 'cursor-not-allowed');
        }
    }
    terms.addEventListener('change', refreshBtn);
    refreshBtn();

    function showError(msg) {
        errText.textContent = msg;
        errBox.classList.remove('hidden');
    }
    function hideError() { errBox.classList.add('hidden'); }

    let processing = false;

    // Confirms the payment without leaving the page
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (processing || btn.disabled) return;
        processing = true;
        hideError();
        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i>PROCESSING…';

        const fail = (msg) => {
            showError(msg);
            btn.innerHTML = originalHtml;
            processing = false;
            refreshBtn();
        };

        // 1) Validate the Payment Element data
        const { error: submitError } = await elements.submit();
        if (submitError) return fail(submitError.message);

        // 2) Create the PaymentIntent on the server and get the client_secret
        let clientSecret;
        try {
            const res = await fetch(intentUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    nom:   form.querySelector('input[name="nom"]').value,
                    email: form.querySelector('input[name="email"]').value,
                }),
            });
            const data = await res.json();
            if (!res.ok) return fail(data.error || 'Could not start the payment. Check your details.');
            clientSecret = data.clientSecret;
        } catch (_) {
            return fail('Could not connect to the payment gateway. Please try again.');
        }

        // 3) Confirm the payment. With redirect:'if_required' cards are charged
        //    on the same page; only methods that require it redirect to return_url.
        const { error, paymentIntent } = await stripe.confirmPayment({
            elements,
            clientSecret,
            confirmParams: { return_url: returnUrl },
            redirect: 'if_required',
        });

        if (error) return fail(error.message || 'The payment could not be completed.');

        if (paymentIntent && paymentIntent.status === 'succeeded') {
            window.location = returnUrl + '?payment_intent=' + encodeURIComponent(paymentIntent.id);
        } else {
            fail('The payment is pending. Please try again.');
        }
    });
})();
</script>
@else
<script>
(function () {
    // Initial state (may come from a reload after a validation error)
    let cardMode      = @json(old('card_mode', 'saved'));   // 'saved' | 'new'
    const hasSaved    = @json((bool) $savedCard);

    // Toggle between saved card and new card form
    window.toggleCardMode = function (mode) {
        cardMode = mode;
        const form   = document.getElementById('newCardForm');
        const hidden = document.getElementById('cardModeHidden');
        if (form) form.classList.toggle('hidden', mode !== 'new');
        if (hidden) hidden.value = mode;
        checkReady();
    };

    // Real-time preview of the bank card
    // Gradients based on the detected card network
    const GRADIENTS = {
        VISA:       'linear-gradient(135deg,#1e3a5f 0%,#2d6a9f 55%,#1a3050 100%)',
        Mastercard: 'linear-gradient(135deg,#8b1a1a 0%,#c0392b 55%,#6b1010 100%)',
        AMEX:       'linear-gradient(135deg,#1a4731 0%,#27ae60 55%,#145a32 100%)',
        default:    'linear-gradient(135deg,#1e3a5f 0%,#2d6a9f 55%,#1a3050 100%)',
    };

    // Detects the card network from the first digits
    function detectBrand(digits) {
        if (/^4/.test(digits))               return 'VISA';
        if (/^5[1-5]|^2[2-7]/.test(digits)) return 'Mastercard';
        if (/^3[47]/.test(digits))           return 'AMEX';
        return 'default';
    }
    function brandIcon(brand) {
        return { VISA: '💳', Mastercard: '🔴', AMEX: '💚', default: '💳' }[brand] || '💳';
    }

    const numInput    = document.getElementById('cardNumInput');
    const nameInput   = document.getElementById('cardNameInput');
    const expiryInput = document.getElementById('cardExpiryInput');
    const cvvInput    = document.getElementById('cardCvvInput');

    // Updates the card number and brand in real time
    numInput?.addEventListener('input', function () {
        const digits = this.value.replace(/\D/g, '').substring(0, 16);
        this.value   = digits.replace(/(.{4})/g, '$1 ').trim();
        const padded = digits.padEnd(16, '*');
        const groups = padded.match(/.{1,4}/g) || [];
        document.getElementById('cardNumDisplay').textContent = groups.join('   ');
        const brand  = detectBrand(digits);
        document.getElementById('cardBrandLbl').textContent  = brand !== 'default' ? brand : 'VISA';
        document.getElementById('cardBrandIcon').textContent = brandIcon(brand);
        const front  = document.getElementById('cardFront');
        if (front) front.style.background = GRADIENTS[brand] || GRADIENTS.default;
        checkReady();
    });

    // Updates the cardholder name in the preview
    nameInput?.addEventListener('input', function () {
        const v = this.value.toUpperCase().substring(0, 22);
        document.getElementById('cardNameDisplay').textContent = v || 'FULL NAME';
        checkReady();
    });

    // Automatically formats MM/YY and updates the preview
    expiryInput?.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '').substring(0, 4);
        if (v.length > 2) v = v.substring(0, 2) + '/' + v.substring(2);
        this.value = v;
        document.getElementById('cardExpiryDisplay').textContent = v || 'MM/YY';
        checkReady();
    });

    // When the CVV is focused, flip the card to show the back (3D flip)
    cvvInput?.addEventListener('focus',  () => document.getElementById('card3d')?.classList.add('flipped'));
    cvvInput?.addEventListener('blur',   () => document.getElementById('card3d')?.classList.remove('flipped'));
    cvvInput?.addEventListener('input', function () {
        const v = this.value.replace(/\D/g, '');
        document.getElementById('cardCvvDisplay').textContent = v ? '•'.repeat(v.length) : '•••';
        checkReady();
    });

    // Validates all fields and enables/disables the pay button
    window.checkReady = function () {
        const termsOk  = document.getElementById('terms').checked;
        let paymentOk  = false;

        if (hasSaved && cardMode === 'saved') {
            paymentOk = true;
        } else {
            const num  = (numInput?.value.replace(/\D/g, '').length  ?? 0) >= 15;
            const name = (nameInput?.value.trim().length              ?? 0) >= 2;
            const exp  = (expiryInput?.value.length                  ?? 0) >= 5;
            const cvv  = (cvvInput?.value.replace(/\D/g, '').length  ?? 0) >= 3;
            paymentOk  = num && name && exp && cvv;
        }

        const ok  = termsOk && paymentOk;
        const btn = document.getElementById('btnRealitzar');
        btn.disabled = !ok;

        if (ok) {
            btn.classList.remove('bg-gray-300', 'cursor-not-allowed');
            btn.classList.add('bg-[#1e3a5f]', 'hover:bg-[#152a45]', 'cursor-pointer', 'shadow-[0_6px_20px_rgba(30,58,95,.35)]');
        } else {
            btn.classList.remove('bg-[#1e3a5f]', 'hover:bg-[#152a45]', 'cursor-pointer', 'shadow-[0_6px_20px_rgba(30,58,95,.35)]');
            btn.classList.add('bg-gray-300', 'cursor-not-allowed');
        }
    };

    document.getElementById('terms').addEventListener('change', checkReady);

    // Restore visual state if the page reloads after a validation error
    if (hasSaved && cardMode !== 'new') toggleCardMode('saved');
    checkReady();
})();
</script>
@endif
@endsection
