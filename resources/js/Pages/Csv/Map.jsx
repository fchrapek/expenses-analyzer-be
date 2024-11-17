import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Map({ auth, csvFile, errors }) {
    const [mappings, setMappings] = useState(csvFile.existingMappings || {});
    const [validationError, setValidationError] = useState('');
    
    const requiredFields = ['date', 'amount', 'description'];
    const mappingOptions = {
        date: 'Transaction Date',
        type: 'Transaction Type',
        amount: 'Amount',
        description: 'Description',
        recipient: 'Recipient/Sender',
        currency: 'Currency',
    };

    const handleMappingChange = (header, value) => {
        setMappings(prev => ({
            ...prev,
            [header]: value
        }));
        setValidationError('');
    };

    const validateMappings = () => {
        const selectedFields = Object.values(mappings);
        const missingRequired = requiredFields.filter(field => !selectedFields.includes(field));
        
        if (missingRequired.length > 0) {
            setValidationError(`Please map the following required fields: ${missingRequired.map(field => mappingOptions[field]).join(', ')}`);
            return false;
        }
        return true;
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!validateMappings()) return;
        
        router.post(`/csv/${csvFile.id}/map`, { 
            mappings,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(`/csv/${csvFile.id}/view`);
            },
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Map CSV Columns</h2>}
        >
            <Head title="Map CSV Columns" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {validationError && (
                                <div className="mb-4 p-4 text-red-700 bg-red-100 rounded-lg">
                                    {validationError}
                                </div>
                            )}
                            {errors && Object.keys(errors).length > 0 && (
                                <div className="mb-4 p-4 text-red-700 bg-red-100 rounded-lg">
                                    {Object.values(errors).map((error, index) => (
                                        <div key={index}>{error}</div>
                                    ))}
                                </div>
                            )}
                            <div className="mb-4">
                                <h3 className="text-lg font-medium text-gray-900">Required Fields:</h3>
                                <p className="text-sm text-gray-600">Transaction Date, Amount, Description</p>
                            </div>
                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="space-y-4">
                                    {csvFile.headers.map((header, index) => (
                                        <div key={index} className="flex items-center space-x-4">
                                            <label className="w-1/3 text-sm font-medium text-gray-700">
                                                {header}
                                            </label>
                                            <select
                                                className="mt-1 block w-2/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={mappings[header] || ''}
                                                onChange={(e) => handleMappingChange(header, e.target.value)}
                                            >
                                                <option value="">Don't map this column</option>
                                                {Object.entries(mappingOptions).map(([key, label]) => (
                                                    <option key={key} value={key}>
                                                        {label}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    ))}
                                </div>

                                <div className="flex justify-between items-center">
                                    <button
                                        type="submit"
                                        className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        Save Mappings
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => router.visit(`/csv/${csvFile.id}/view`)}
                                        className="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
