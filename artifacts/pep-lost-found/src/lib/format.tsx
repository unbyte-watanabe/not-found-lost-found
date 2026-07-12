import { format } from "date-fns";
import { ja } from "date-fns/locale";
import React, { useState } from "react";
import { Eye, EyeOff } from "lucide-react";

export function formatDateTime(dateString: string | null | undefined): string {
  if (!dateString) return "—";
  try {
    return format(new Date(dateString), "yyyy年MM月dd日 HH:mm", { locale: ja });
  } catch (e) {
    return "—";
  }
}

export function formatDate(dateString: string | null | undefined): string {
  if (!dateString) return "—";
  try {
    return format(new Date(dateString), "yyyy年MM月dd日", { locale: ja });
  } catch (e) {
    return "—";
  }
}

export function getStatusColor(status: string) {
  switch (status) {
    case "保管中":
      return "bg-blue-100 text-blue-800 border-blue-200";
    case "返還済":
      return "bg-green-100 text-green-800 border-green-200";
    case "警察提出済":
      return "bg-orange-100 text-orange-800 border-orange-200";
    case "期間満了処分":
      return "bg-gray-200 text-gray-800 border-gray-300";
    case "探索中":
      return "bg-blue-100 text-blue-800 border-blue-200";
    case "解決済":
      return "bg-green-100 text-green-800 border-green-200";
    case "キャンセル":
      return "bg-gray-200 text-gray-800 border-gray-300";
    default:
      return "bg-gray-100 text-gray-800 border-gray-200";
  }
}

export function getCategoryColor(category: string) {
  switch (category) {
    case "財布・カバン類":
      return "bg-amber-100 text-amber-800 border-amber-200";
    case "衣類":
      return "bg-indigo-100 text-indigo-800 border-indigo-200";
    case "電子機器":
      return "bg-rose-100 text-rose-800 border-rose-200";
    case "傘":
      return "bg-teal-100 text-teal-800 border-teal-200";
    case "その他":
      return "bg-slate-100 text-slate-800 border-slate-200";
    default:
      return "bg-slate-100 text-slate-800 border-slate-200";
  }
}

export function getMatchScoreColor(score: number) {
  if (score >= 70) return "text-green-600 font-bold";
  if (score >= 40) return "text-yellow-600 font-bold";
  return "text-red-600 font-bold";
}

export function getMatchScoreBgColor(score: number) {
  if (score >= 70) return "bg-green-100 border-green-200 text-green-800";
  if (score >= 40) return "bg-yellow-100 border-yellow-200 text-yellow-800";
  return "bg-red-100 border-red-200 text-red-800";
}

export function MaskedText({ text }: { text: string | null | undefined }) {
  const [show, setShow] = useState(false);
  
  if (!text) return <span className="text-muted-foreground">—</span>;

  return (
    <div className="flex items-center gap-2 group">
      <span>{show ? text : "••••••••"}</span>
      <button 
        type="button"
        onClick={(e) => { e.preventDefault(); e.stopPropagation(); setShow(!show); }}
        className="text-muted-foreground hover:text-foreground transition-colors p-1 rounded-md hover:bg-secondary flex items-center justify-center"
        aria-label={show ? "隠す" : "表示"}
      >
        {show ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
      </button>
    </div>
  );
}