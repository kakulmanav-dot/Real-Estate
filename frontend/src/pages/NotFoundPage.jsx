import { Link } from "react-router-dom";

export default function NotFoundPage() {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center text-center px-6">
      <h1 className="text-6xl font-bold text-blue-600">404</h1>
      <p className="text-gray-600 mt-4 text-lg">The page you're looking for doesn't exist.</p>
      <Link to="/" className="mt-6 bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
        Back to Home
      </Link>
    </div>
  );
}
