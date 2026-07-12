import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from '@/components/ui/toaster';
import { TooltipProvider } from '@/components/ui/tooltip';
import { Route, Switch, Router as WouterRouter } from 'wouter';

import { Layout } from '@/components/layout';
import Dashboard from '@/pages/dashboard';
import FoundItemsList from '@/pages/found-items/index';
import NewFoundItem from '@/pages/found-items/new';
import FoundItemDetail from '@/pages/found-items/detail';
import LostReportsList from '@/pages/lost-reports/index';
import NewLostReport from '@/pages/lost-reports/new';
import LostReportDetail from '@/pages/lost-reports/detail';
import MatchesList from '@/pages/matches/index';
import ExportPage from '@/pages/export/index';
import NotFound from '@/pages/not-found';

const queryClient = new QueryClient();

function Router() {
  return (
    <Layout>
      <Switch>
        <Route path="/" component={Dashboard} />
        
        {/* Found Items */}
        <Route path="/found-items" component={FoundItemsList} />
        <Route path="/found-items/new" component={NewFoundItem} />
        <Route path="/found-items/:id" component={FoundItemDetail} />
        
        {/* Lost Reports */}
        <Route path="/lost-reports" component={LostReportsList} />
        <Route path="/lost-reports/new" component={NewLostReport} />
        <Route path="/lost-reports/:id" component={LostReportDetail} />
        
        {/* Others */}
        <Route path="/matches" component={MatchesList} />
        <Route path="/export" component={ExportPage} />
        
        <Route component={NotFound} />
      </Switch>
    </Layout>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <WouterRouter base={import.meta.env.BASE_URL.replace(/\/$/, '')}>
          <Router />
        </WouterRouter>
        <Toaster />
      </TooltipProvider>
    </QueryClientProvider>
  );
}

export default App;
