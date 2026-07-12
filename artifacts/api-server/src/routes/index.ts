import { Router, type IRouter } from "express";
import healthRouter from "./health";
import dashboardRouter from "./dashboard";
import foundItemsRouter from "./found-items";
import lostReportsRouter from "./lost-reports";
import matchesRouter from "./matches";
import uploadRouter from "./upload";

const router: IRouter = Router();

router.use(healthRouter);
router.use(dashboardRouter);
router.use(foundItemsRouter);
router.use(lostReportsRouter);
router.use(matchesRouter);
router.use(uploadRouter);

export default router;
