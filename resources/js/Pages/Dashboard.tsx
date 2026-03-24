import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEventHandler } from 'react';
import type { SharedProps } from '../types';
import Header from '../components/Header';

interface DashboardProps extends SharedProps {
    name: string;
    api_email: string;
    api_password: string;
    [key: string]: unknown;
}

interface ChargeFormData {
    amount: string;
    currency: string;
    description: string;
    card_number: string;
    cvv: string;
    expiry_month: string;
    expiry_year: string;
}

const initialFormData: ChargeFormData = {
    amount: '',
    currency: 'EUR',
    description: '',
    card_number: '',
    cvv: '',
    expiry_month: '',
    expiry_year: '',
};

export default function Dashboard() {
    const { name, api_email, api_password } = usePage<DashboardProps>().props;

    const tokenRef = useRef<string | null>(null);

    const [formData, setFormData] = useState<ChargeFormData>(initialFormData);
    const [errors, setErrors] = useState<Partial<Record<keyof ChargeFormData, string>>>({});
    const [processing, setProcessing] = useState(false);
    const [flash, setFlash] = useState<{ success: string | null; error: string | null }>({
        success: null,
        error: null,
    });

    useEffect(() => {
        if (!flash.success && !flash.error) return;
        const timer = setTimeout(() => setFlash({ success: null, error: null }), 4000);
        return () => clearTimeout(timer);
    }, [flash.success, flash.error]);

    useEffect(() => {
        axios
            .post('/api/v1/tokens', { email: api_email, password: api_password, device_name: 'dashboard' })
            .then((res) => {
                tokenRef.current = res.data.token;
            })
            .catch(() => {
                setFlash({ success: null, error: 'Could not authenticate with the API. Please refresh.' });
            });
    }, []);

    const handleCharge: FormEventHandler = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setFlash({ success: null, error: null });

        try {
            const res = await axios.post(
                '/api/v1/charges',
                {
                    amount: parseInt(formData.amount, 10),
                    currency: formData.currency || 'EUR',
                    description: formData.description || undefined,
                    card_number: formData.card_number,
                    cvv: formData.cvv,
                    expiry_month: parseInt(formData.expiry_month, 10),
                    expiry_year: parseInt(formData.expiry_year, 10),
                },
                {
                    headers: { Authorization: `Bearer ${tokenRef.current}` },
                },
            );

            if (res.data?.data?.status === 'failed') {
                setFlash({ success: null, error: 'Payment failed. The card was declined.' });
            } else {
                setFlash({ success: 'Payment processed successfully!', error: null });
                setFormData(initialFormData);
            }
        } catch (err: any) {
            if (err.response?.status === 422) {
                const apiErrors: Record<string, string[]> = err.response.data.errors ?? {};
                const flat: Partial<Record<keyof ChargeFormData, string>> = {};
                for (const [key, messages] of Object.entries(apiErrors)) {
                    flat[key as keyof ChargeFormData] = messages[0];
                }
                setErrors(flat);
            } else {
                setFlash({
                    success: null,
                    error: err.response?.data?.message ?? 'Payment failed. Please try again.',
                });
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <Header name={name} />

            <main className="max-w-5xl mx-auto px-4 py-8 space-y-8">
                {/* Flash messages */}
                {flash.success && (
                    <div className="flex items-center justify-between bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm">
                        <span>{flash.success}</span>
                        <button
                            type="button"
                            onClick={() => setFlash((prev) => ({ ...prev, success: null }))}
                            className="ml-4 text-green-600 hover:text-green-900 font-bold leading-none"
                        >
                            &times;
                        </button>
                    </div>
                )}
                {flash.error && (
                    <div className="flex items-center justify-between bg-red-50 border border-red-200 text-red-800 rounded-md px-4 py-3 text-sm">
                        <span>{flash.error}</span>
                        <button
                            type="button"
                            onClick={() => setFlash((prev) => ({ ...prev, error: null }))}
                            className="ml-4 text-red-600 hover:text-red-900 font-bold leading-none"
                        >
                            &times;
                        </button>
                    </div>
                )}

                {/* Charge form */}
                <section className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Process Payment</h2>
                    <form onSubmit={handleCharge} className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div className="sm:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Amount (in cents, e.g. 1000 = €10.00) <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                min="1"
                                required
                                placeholder="1000"
                                value={formData.amount}
                                onChange={(e) => setFormData((prev) => ({ ...prev, amount: e.target.value }))}
                                disabled={processing}
                                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            {errors.amount && (
                                <p className="mt-1 text-sm text-red-600">{errors.amount}</p>
                            )}
                        </div>

                        <div className="sm:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input
                                type="text"
                                placeholder="Payment description"
                                maxLength={255}
                                value={formData.description}
                                onChange={(e) => setFormData((prev) => ({ ...prev, description: e.target.value }))}
                                disabled={processing}
                                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <div className="sm:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Card Number <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                required
                                placeholder="4242 4242 4242 4242"
                                value={formData.card_number}
                                onChange={(e) =>
                                    setFormData((prev) => ({ ...prev, card_number: e.target.value.replace(/\s/g, '') }))
                                }
                                disabled={processing}
                                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            {errors.card_number && (
                                <p className="mt-1 text-sm text-red-600">{errors.card_number}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                CVV <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                required
                                placeholder="123"
                                maxLength={4}
                                value={formData.cvv}
                                onChange={(e) => setFormData((prev) => ({ ...prev, cvv: e.target.value }))}
                                disabled={processing}
                                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            {errors.cvv && (
                                <p className="mt-1 text-sm text-red-600">{errors.cvv}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Expiry Month <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                required
                                placeholder="12"
                                min={1}
                                max={12}
                                value={formData.expiry_month}
                                onChange={(e) => setFormData((prev) => ({ ...prev, expiry_month: e.target.value }))}
                                disabled={processing}
                                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            {errors.expiry_month && (
                                <p className="mt-1 text-sm text-red-600">{errors.expiry_month}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Expiry Year <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="number"
                                required
                                placeholder="2028"
                                min={new Date().getFullYear()}
                                value={formData.expiry_year}
                                onChange={(e) => setFormData((prev) => ({ ...prev, expiry_year: e.target.value }))}
                                disabled={processing}
                                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            {errors.expiry_year && (
                                <p className="mt-1 text-sm text-red-600">{errors.expiry_year}</p>
                            )}
                        </div>

                        <div className="sm:col-span-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-blue-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 transition"
                            >
                                {processing ? 'Processing…' : 'Charge Card'}
                            </button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    );
}
