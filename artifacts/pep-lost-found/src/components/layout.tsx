import React from "react";
import { Link, useLocation } from "wouter";
import { 
  LayoutDashboard, 
  PackageSearch, 
  Search, 
  ClipboardList, 
  FileDown,
  Menu,
  Plus
} from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTrigger, SheetTitle, SheetHeader } from "@/components/ui/sheet";

interface LayoutProps {
  children: React.ReactNode;
}

export function Layout({ children }: LayoutProps) {
  const [location] = useLocation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = React.useState(false);

  const navItems = [
    { href: "/", label: "ダッシュボード", icon: LayoutDashboard },
    { href: "/found-items", label: "拾得物一覧", icon: PackageSearch },
    { href: "/lost-reports", label: "紛失届一覧", icon: Search },
    { href: "/matches", label: "マッチング確認", icon: ClipboardList },
    { href: "/export", label: "警察提出出力", icon: FileDown },
  ];

  const NavLinks = () => (
    <div className="space-y-1">
      {navItems.map((item) => {
        const isActive = location === item.href || (item.href !== "/" && location.startsWith(item.href));
        const Icon = item.icon;
        
        return (
          <Link key={item.href} href={item.href}>
            <span
              onClick={() => setIsMobileMenuOpen(false)}
              className={cn(
                "flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium transition-colors cursor-pointer",
                isActive 
                  ? "bg-primary text-primary-foreground shadow-sm" 
                  : "text-muted-foreground hover:bg-secondary hover:text-foreground"
              )}
            >
              <Icon className="w-5 h-5" />
              {item.label}
            </span>
          </Link>
        );
      })}
    </div>
  );

  return (
    <div className="min-h-[100dvh] flex flex-col md:flex-row bg-background">
      {/* Mobile Header */}
      <header className="md:hidden sticky top-0 z-40 bg-background/80 backdrop-blur-lg border-b border-border px-4 py-3 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Sheet open={isMobileMenuOpen} onOpenChange={setIsMobileMenuOpen}>
            <SheetTrigger asChild>
              <Button variant="ghost" size="icon" className="-ml-2">
                <Menu className="w-5 h-5" />
              </Button>
            </SheetTrigger>
            <SheetContent side="left" className="w-[280px] p-0 border-r-0 bg-background">
              <SheetHeader className="p-6 border-b border-border/50 text-left">
                <SheetTitle className="text-xl font-bold tracking-tight text-primary">
                  PEP落とし物管理
                </SheetTitle>
              </SheetHeader>
              <div className="p-4">
                <NavLinks />
              </div>
            </SheetContent>
          </Sheet>
          <span className="font-bold text-primary text-lg">PEP落とし物管理</span>
        </div>
      </header>

      {/* Desktop Sidebar */}
      <aside className="hidden md:flex w-[260px] flex-col border-r border-border/50 bg-card fixed inset-y-0 shadow-[4px_0_24px_rgba(66,55,50,0.03)] z-10">
        <div className="p-6 border-b border-border/50">
          <h1 className="text-xl font-bold tracking-tight text-primary">
            PEP落とし物管理
          </h1>
          <p className="text-xs text-muted-foreground mt-1">PlayEarthPark</p>
        </div>
        <div className="p-4 flex-1 overflow-y-auto">
          <NavLinks />
        </div>
        <div className="p-4 border-t border-border/50">
          <div className="flex items-center gap-3 px-3 py-2 text-sm text-muted-foreground">
            <div className="w-8 h-8 rounded-full bg-secondary flex items-center justify-center font-bold text-foreground">
              ST
            </div>
            <div>
              <p className="font-medium text-foreground">Staff User</p>
              <p className="text-xs">staff@pep.example.com</p>
            </div>
          </div>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 md:ml-[260px] w-full max-w-5xl mx-auto p-4 md:p-8 lg:p-12 pb-24 md:pb-12">
        {children}
      </main>
      
      {/* Mobile Floating Action Button (FAB) for quick add - only on certain pages */}
      <div className="md:hidden fixed bottom-6 right-6 z-40">
        {(location === "/found-items" || location === "/lost-reports") && (
          <Link href={location + "/new"}>
            <Button size="icon" className="w-14 h-14 rounded-full shadow-lg hover-elevate-2">
              <Plus className="w-6 h-6" />
            </Button>
          </Link>
        )}
      </div>
    </div>
  );
}