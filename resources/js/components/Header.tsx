import { router } from '@inertiajs/react';

type HeaderProps = {
    name: string;
};

export default function Header({ name }: HeaderProps) {
    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <header className="bg-white shadow-sm">
            <div className="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
                <h1 className="text-xl font-bold text-gray-900">EveryPay</h1>
                <div className="flex items-center gap-4">
                    <a href='/dashboard' className="text-sm text-gray-600">{name}</a>
                    <a href='/transactions' className="text-sm text-gray-600">Transactions</a>
                    <button
                        onClick={handleLogout}
                        className="text-sm text-red-600 hover:underline"
                    >
                        Sign out
                    </button>
                </div>
            </div>
        </header>
    );
}
