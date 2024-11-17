import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Dashboard({ auth, csv_files }) {
    const [isUploading, setIsUploading] = useState(false);
    const { data, setData, post, progress } = useForm({
        csv_file: null
    });

    const handleFileUpload = (e) => {
        const file = e.target.files[0];
        setData('csv_file', file);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsUploading(true);
        post(route('csv.upload'), {
            onSuccess: () => {
                setIsUploading(false);
            },
            onError: () => {
                setIsUploading(false);
            }
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Upload Form */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <form onSubmit={handleSubmit} className="flex items-center space-x-4">
                                <input
                                    type="file"
                                    onChange={handleFileUpload}
                                    accept=".csv"
                                    className="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-full file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100"
                                />
                                <button
                                    type="submit"
                                    disabled={!data.csv_file || isUploading}
                                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600
                                             disabled:bg-blue-300 disabled:cursor-not-allowed"
                                >
                                    {isUploading ? 'Uploading...' : 'Upload'}
                                </button>
                            </form>

                            {/* Upload Progress */}
                            {progress && (
                                <div className="mt-4">
                                    <div className="w-full bg-gray-200 rounded-full h-2.5">
                                        <div
                                            className="bg-blue-600 h-2.5 rounded-full"
                                            style={{ width: `${progress}%` }}
                                        ></div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Files List Placeholder */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {csv_files && csv_files.length > 0 ? (
                                csv_files.map(file => (
                                    <div key={file.id} className="mb-4">
                                        <h3 className="font-bold">{file.original_filename}</h3>
                                        <p>Number of entries: {file.entries?.length || 0}</p>
                                    </div>
                                ))
                            ) : (
                                <p>No CSV files found</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
