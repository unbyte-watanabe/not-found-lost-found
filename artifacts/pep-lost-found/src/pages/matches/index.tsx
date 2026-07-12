import React from "react";
import { Link } from "wouter";
import { ArrowRight, PackageSearch, Search, AlertCircle } from "lucide-react";
import { useListMatches } from "@workspace/api-client-react";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { formatDate, getMatchScoreColor, getMatchScoreBgColor, MaskedText } from "@/lib/format";

export default function MatchesList() {
  const { data: matches, isLoading } = useListMatches({
    minScore: 40 // Only show promising matches
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <div>
        <h1 className="text-2xl md:text-3xl font-bold text-foreground">マッチング確認</h1>
        <p className="text-muted-foreground mt-1 text-sm md:text-base">
          システムが検知した「拾得物」と「紛失届」の類似候補一覧です。
        </p>
      </div>

      {isLoading ? (
        <div className="space-y-4">
          {[1, 2, 3].map(i => (
            <Skeleton key={i} className="w-full h-40 rounded-xl" />
          ))}
        </div>
      ) : !matches || matches.length === 0 ? (
        <div className="text-center py-20 bg-card rounded-xl border border-border/50 border-dashed">
          <AlertCircle className="w-12 h-12 text-muted-foreground/30 mx-auto mb-4" />
          <p className="text-muted-foreground">現在、確認が必要なマッチング候補はありません</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-6">
          {matches.map((match) => (
            <Card key={match.id} className={`border border-border/50 shadow-sm overflow-hidden flex flex-col md:flex-row relative`}>
              {/* Score Badge */}
              <div className={`absolute top-0 right-0 rounded-bl-xl px-4 py-1.5 font-bold ${getMatchScoreBgColor(match.score)} z-10 border-l border-b border-border/50`}>
                一致度 {match.score}%
              </div>

              {/* Found Item Side */}
              <div className="flex-1 p-5 md:pr-10 relative">
                <div className="flex items-center gap-2 mb-3">
                  <PackageSearch className="w-4 h-4 text-primary" />
                  <span className="font-bold text-sm text-primary">拾得物 (施設にある)</span>
                </div>
                
                <div className="flex gap-4">
                  <div className="w-16 h-16 rounded-md bg-secondary/50 border border-border overflow-hidden shrink-0">
                    {match.foundItem.imageUrl ? (
                      <img src={match.foundItem.imageUrl} alt="" className="w-full h-full object-cover" />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-muted-foreground/30">
                        <PackageSearch className="w-6 h-6" />
                      </div>
                    )}
                  </div>
                  <div className="space-y-1">
                    <p className="font-medium text-sm line-clamp-2">{match.foundItem.features}</p>
                    <p className="text-xs text-muted-foreground">拾得: {formatDate(match.foundItem.foundDatetime)}</p>
                    <p className="text-xs text-muted-foreground">場所: {match.foundItem.foundLocation || "不明"}</p>
                    <div className="pt-2">
                      <Link href={`/found-items/${match.foundItem.id}`}>
                        <Button variant="link" className="h-auto p-0 text-xs text-primary">詳細を見る</Button>
                      </Link>
                    </div>
                  </div>
                </div>
              </div>

              {/* Separator / Arrow */}
              <div className="md:w-16 flex items-center justify-center py-2 md:py-0 bg-secondary/10 md:bg-transparent">
                <ArrowRight className="w-6 h-6 text-muted-foreground/40 hidden md:block rotate-0" />
                <ArrowRight className="w-6 h-6 text-muted-foreground/40 block md:hidden rotate-90" />
              </div>

              {/* Lost Report Side */}
              <div className="flex-1 p-5 md:pl-10 bg-secondary/10 border-t md:border-t-0 md:border-l border-border/50 border-dashed">
                <div className="flex items-center gap-2 mb-3">
                  <Search className="w-4 h-4 text-indigo-600" />
                  <span className="font-bold text-sm text-indigo-600">紛失届 (探している)</span>
                </div>
                
                <div className="space-y-2">
                  <p className="font-medium text-sm line-clamp-2">{match.lostReport.features}</p>
                  <p className="text-xs text-muted-foreground">紛失: {match.lostReport.lostDatetimeFrom ? formatDate(match.lostReport.lostDatetimeFrom) : "不明"}頃</p>
                  <p className="text-xs text-muted-foreground">場所: {match.lostReport.lostLocationEstimated || "不明"}</p>
                  <div className="flex items-center gap-2 text-xs pt-1">
                    <span className="text-muted-foreground">届出人:</span>
                    <MaskedText text={match.lostReport.ownerName} />
                  </div>
                  <div className="pt-1">
                    <Link href={`/lost-reports/${match.lostReport.id}`}>
                      <Button variant="link" className="h-auto p-0 text-xs text-indigo-600">紛失届を開く</Button>
                    </Link>
                  </div>
                </div>
              </div>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}