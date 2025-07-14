import { Metadata } from "next";

export const metadata: Metadata = {
  title: "Tvarkaraštis",
};

export default function TvarkarastisPage() {
  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-semibold text-gray-900">Tvarkaraštis</h1>
      </div>
      <div className="bg-white rounded-lg shadow">
        <div className="p-6">
          {/* Tvarkaraščio turinys bus čia */}
          <p className="text-gray-600">Tvarkaraščio informacija bus pridėta vėliau</p>
        </div>
      </div>
    </div>
  );
} 